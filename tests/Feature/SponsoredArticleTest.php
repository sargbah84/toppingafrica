<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\ManagePromotions;
use App\Mail\PromotionAdminAlert;
use App\Mail\PromotionApproved;
use App\Mail\PromotionInquiryReceived;
use App\Models\Post;
use App\Models\PromotionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SponsoredArticleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_x',
            'promotions.notify_email' => 'team@example.com',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'package' => 'sponsored_article',
            'name' => 'Lauren Hunt',
            'subject_name' => 'Acme Fintech',
            'title' => 'How mobile money is changing Lagos',
            'article_body' => "Intro paragraph about mobile money in Lagos.\n\n".trim(str_repeat('word ', 350)),
            'primary_url' => 'https://acme.example/payments',
            'anchor_text' => 'mobile money',
            'rights_confirmed' => '1',
            'terms' => '1',
        ];
    }

    private function makeSubmission(User $user, array $attributes = []): PromotionRequest
    {
        return PromotionRequest::create($attributes + [
            'user_id' => $user->id,
            'package' => 'sponsored_article',
            'addons' => [],
            'amount' => 9900,
            'currency' => 'usd',
            'name' => $user->name,
            'email' => $user->email,
            'promo_type' => PromotionRequest::SPONSORED_ARTICLE,
            'subject_name' => 'Acme Fintech',
            'title' => 'How mobile money is changing Lagos',
            'description' => '',
            'article_body' => "Mobile Money is growing fast.\n\nSecond <b>paragraph</b>.",
            'primary_url' => 'https://acme.example/payments',
            'anchor_text' => 'mobile money',
            'rights_confirmed' => true,
            'status' => 'inquiry',
        ]);
    }

    public function test_promote_page_lists_sponsored_packages(): void
    {
        $this->get('/promote')
            ->assertOk()
            ->assertSee('Sponsored Article + Social')
            ->assertSee('$99')
            ->assertSee(route('sponsored.create', ['package' => 'sponsored_article']), false);
    }

    public function test_submitting_requires_a_verified_account(): void
    {
        $this->get('/promote/sponsored-article')->assertRedirect('/login');
        $this->post('/promote/sponsored-article', $this->validPayload())->assertRedirect('/login');

        $unverified = User::factory()->unverified()->create();

        $this->actingAs($unverified)->get('/promote/sponsored-article')->assertRedirect(route('verification.notice'));
        $this->actingAs($unverified)->post('/promote/sponsored-article', $this->validPayload())
            ->assertRedirect(route('verification.notice'));

        $this->assertDatabaseCount('promotion_requests', 0);
    }

    public function test_submission_goes_to_review_and_makes_the_user_a_contributor(): void
    {
        Mail::fake();
        // Even with online payments on, nothing is charged before review.
        config(['promotions.online_payments' => true]);

        $user = User::factory()->create(['email' => 'lauren@example.com']);
        $user->syncRoles(['regular']);

        $this->actingAs($user)->get('/promote/sponsored-article?package=sponsored_article_plus')
            ->assertOk()
            ->assertSee('Submit for review');

        $this->actingAs($user)
            ->post('/promote/sponsored-article', $this->validPayload(['email' => 'someone-else@example.com']))
            ->assertRedirect(route('dashboard.sponsored'));

        $submission = PromotionRequest::sole();
        $this->assertSame('inquiry', $submission->status);
        $this->assertSame(9900, $submission->amount);
        $this->assertSame([], $submission->addons);
        $this->assertSame($user->id, $submission->user_id);
        $this->assertSame('lauren@example.com', $submission->email);
        $this->assertTrue($submission->isSponsoredArticle());
        $this->assertSame('Sponsored Article', $submission->package_name);

        $this->assertSame(['contributor'], $user->fresh()->getRoleNames()->all());

        Mail::assertSent(PromotionInquiryReceived::class, fn ($mail) => $mail->hasTo('lauren@example.com'));
        Mail::assertSent(PromotionAdminAlert::class, fn ($mail) => $mail->hasTo('team@example.com'));

        $this->actingAs($user)->get('/dashboard/sponsored')
            ->assertOk()
            ->assertSee('How mobile money is changing Lagos')
            ->assertSee('In review');
    }

    public function test_existing_roles_are_kept(): void
    {
        Mail::fake();
        $creator = User::factory()->create();
        $creator->syncRoles(['creator']);

        $this->actingAs($creator)->post('/promote/sponsored-article', $this->validPayload())->assertRedirect();

        $this->assertSame(['creator'], $creator->fresh()->getRoleNames()->all());
    }

    public function test_article_length_is_enforced_per_package(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/promote/sponsored-article', $this->validPayload(['article_body' => 'Far too short.']))
            ->assertSessionHasErrors('article_body');

        $long = trim(str_repeat('word ', 1300));

        $this->actingAs($user)
            ->post('/promote/sponsored-article', $this->validPayload(['article_body' => $long]))
            ->assertSessionHasErrors('article_body');

        $this->assertDatabaseCount('promotion_requests', 0);

        Mail::fake();
        $this->actingAs($user)
            ->post('/promote/sponsored-article', $this->validPayload(['package' => 'sponsored_article_plus', 'article_body' => $long]))
            ->assertSessionHasNoErrors();

        $this->assertSame(14900, PromotionRequest::sole()->amount);
    }

    public function test_sponsored_packages_cannot_be_bought_through_the_promote_form(): void
    {
        $this->post('/promote/checkout', ['package' => 'sponsored_article'])->assertSessionHasErrors('package');
    }

    public function test_dashboard_lists_only_own_submissions_with_a_pay_button_once_approved(): void
    {
        config(['promotions.online_payments' => true]);

        $user = User::factory()->create();
        $mine = $this->makeSubmission($user, ['status' => 'invoiced']);
        $this->makeSubmission(User::factory()->create(), ['title' => 'Someone else’s article']);

        $this->actingAs($user)->get('/dashboard/sponsored')
            ->assertOk()
            ->assertSee('awaiting payment')
            ->assertSee('Pay $99')
            ->assertDontSee('Someone else');

        $this->assertTrue($mine->awaitingPayment());
    }

    public function test_approval_emails_a_payment_link_when_online_payments_are_on(): void
    {
        Mail::fake();
        config(['promotions.online_payments' => true]);

        $staff = User::factory()->create(['is_staff' => true]);
        $submission = $this->makeSubmission(User::factory()->create(['email' => 'lauren@example.com']));

        Livewire::actingAs($staff)
            ->test(ManagePromotions::class)
            ->call('open', $submission->reference)
            ->assertSee('Their article')
            ->set('status', 'invoiced')
            ->call('save')
            ->assertHasNoErrors()
            ->call('save');

        $this->assertNull($submission->fresh()->paid_at);
        Mail::assertSent(PromotionApproved::class, 1);
        Mail::assertSent(PromotionApproved::class, fn ($mail) => $mail->hasTo('lauren@example.com'));
    }

    public function test_approval_sends_no_payment_link_when_invoicing_by_hand(): void
    {
        Mail::fake();

        $staff = User::factory()->create(['is_staff' => true]);
        $submission = $this->makeSubmission(User::factory()->create());

        Livewire::actingAs($staff)
            ->test(ManagePromotions::class)
            ->call('open', $submission->reference)
            ->set('status', 'invoiced')
            ->call('save');

        Mail::assertNothingSent();
    }

    public function test_draft_post_is_built_from_the_article_with_the_link_on_the_anchor_text(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $submission = $this->makeSubmission(User::factory()->create(), ['status' => 'paid']);

        Livewire::actingAs($staff)
            ->test(ManagePromotions::class)
            ->call('open', $submission->reference)
            ->call('createDraftPost')
            ->assertRedirect();

        $post = Post::sole();
        $this->assertSame('How mobile money is changing Lagos', $post->title);
        $this->assertTrue($post->is_sponsored);
        $this->assertSame('draft', $post->status);
        $this->assertSame(
            '<p><a href="https://acme.example/payments">Mobile Money</a> is growing fast.</p><p>Second &lt;b&gt;paragraph&lt;/b&gt;.</p>',
            $post->content,
        );
    }

    public function test_link_is_appended_when_the_anchor_text_is_not_in_the_article(): void
    {
        $submission = new PromotionRequest([
            'article_body' => 'Only paragraph.',
            'primary_url' => 'https://acme.example',
            'anchor_text' => 'Acme',
        ]);

        $this->assertSame(
            '<p>Only paragraph.</p><p><a href="https://acme.example">Acme</a></p>',
            $submission->articleHtml(),
        );
    }

    public function test_contributor_role_exists_without_permissions(): void
    {
        $this->assertCount(0, Role::findByName('contributor')->permissions);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\ManagePromotions;
use App\Mail\PromotionAdminAlert;
use App\Mail\PromotionInquiryReceived;
use App\Mail\PromotionLive;
use App\Mail\PromotionReceipt;
use App\Models\Page;
use App\Models\Post;
use App\Models\PromotionRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\Promotions\PromotionCheckout;
use App\Services\Promotions\PromotionPricing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use RefreshDatabase;

    private const WEBHOOK_SECRET = 'whsec_test';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.stripe.secret' => 'sk_test_x',
            'services.stripe.webhook_secret' => self::WEBHOOK_SECRET,
            'promotions.notify_email' => 'team@example.com',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return $overrides + [
            'package' => 'boost',
            'addons' => ['rush'],
            'name' => 'Danny Attah',
            'email' => 'danny@example.com',
            'promo_type' => 'video',
            'subject_name' => 'Danny Attah',
            'title' => 'Praise Partner (Official Video)',
            'description' => 'A vibrant new gospel music video celebrating gratitude and joy.',
            'primary_url' => 'https://youtube.com/watch?v=abc',
            'links' => ['instagram' => 'https://instagram.com/danny', 'bogus' => 'https://evil.test'],
            'rights_confirmed' => '1',
            'terms' => '1',
        ];
    }

    private function makePromotion(array $attributes = []): PromotionRequest
    {
        return PromotionRequest::create($attributes + [
            'package' => 'feature',
            'addons' => [],
            'amount' => 7900,
            'currency' => 'usd',
            'name' => 'Ama',
            'email' => 'ama@example.com',
            'promo_type' => 'music',
            'subject_name' => 'Ama',
            'title' => 'New Single',
            'description' => str_repeat('Great song. ', 5),
            'primary_url' => 'https://example.com/song',
            'rights_confirmed' => true,
            'status' => 'pending_payment',
        ]);
    }

    private function mockCheckoutSession(): void
    {
        $this->mock(PromotionCheckout::class, function ($mock) {
            $mock->shouldReceive('isEnabled')->andReturn(true);
            $mock->shouldReceive('createSession')->andReturn('https://checkout.stripe.com/c/pay/cs_test_1');
        });
    }

    private function postWebhook(array $session): TestResponse
    {
        $payload = json_encode([
            'id' => 'evt_1',
            'object' => 'event',
            'type' => 'checkout.session.completed',
            'data' => ['object' => $session + ['object' => 'checkout.session']],
        ]);
        $timestamp = time();
        $signature = hash_hmac('sha256', "{$timestamp}.{$payload}", self::WEBHOOK_SECRET);

        return $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => "t={$timestamp},v1={$signature}",
            'CONTENT_TYPE' => 'application/json',
        ], $payload);
    }

    public function test_promote_page_lists_packages(): void
    {
        $this->get('/promote')
            ->assertOk()
            ->assertSee('Starter Post')
            ->assertSee('Spotlight')
            ->assertSee('$179')
            ->assertSee(route('promote.checkout', ['package' => 'spotlight']), false)
            ->assertSee('Get your invoice');
    }

    public function test_promote_page_is_a_cms_page_in_the_header_menu(): void
    {
        $page = Page::byTemplate('promote');
        $this->assertNotNull($page);

        $header = json_decode(Setting::get('header_pages', '[]'), true);
        $this->assertContains($page->id, array_column($header, 'id'));

        $this->get('/')->assertSee('href="'.url('/promote').'"', false);

        // Renaming the slug moves the page, and internal links follow it.
        $page->update(['slug' => 'advertise', 'meta_title' => 'Advertise with us', 'content' => '<h1>Get featured</h1>']);

        $this->get('/advertise')->assertOk()->assertSee('Get featured')->assertSee('Advertise with us')->assertSee('Spotlight');
        $this->get('/promote')->assertNotFound();
        $this->get('/promote/checkout')->assertOk()->assertSee(url('/advertise').'#packages', false);
    }

    public function test_checkout_page_preselects_package(): void
    {
        $this->get('/promote/checkout?package=spotlight')
            ->assertOk()
            ->assertSee('Submit request')
            ->assertSee(", 'spotlight',", false);
    }

    public function test_submission_creates_inquiry_and_emails_buyer_and_team(): void
    {
        Mail::fake();

        $response = $this->post('/promote/checkout', $this->validPayload());

        $promotion = PromotionRequest::sole();
        $response->assertRedirect(URL::signedRoute('promote.thanks', $promotion));

        $this->assertSame('inquiry', $promotion->status);
        $this->assertNull($promotion->paid_at);
        $this->assertSame(17900 + 3900, $promotion->amount);
        $this->assertSame(['rush'], $promotion->addons);
        $this->assertSame(['instagram' => 'https://instagram.com/danny'], $promotion->links);
        $this->assertMatchesRegularExpression('/^TA-[A-Z0-9]{6}$/', $promotion->reference);

        Mail::assertSent(PromotionInquiryReceived::class, fn ($mail) => $mail->hasTo('danny@example.com'));
        Mail::assertSent(PromotionAdminAlert::class, fn ($mail) => $mail->hasTo('team@example.com'));

        $this->get(URL::signedRoute('promote.thanks', $promotion))
            ->assertOk()
            ->assertSee('Look out for our next email');
        $this->get(route('promote.thanks', $promotion))->assertNotFound();
    }

    public function test_online_payments_redirect_to_stripe(): void
    {
        $this->mockCheckoutSession();

        $this->post('/promote/checkout', $this->validPayload())
            ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test_1');

        $this->assertSame('pending_payment', PromotionRequest::sole()->status);
    }

    public function test_online_payments_stay_off_by_default_even_with_stripe_keys(): void
    {
        $this->assertFalse(app(PromotionCheckout::class)->isEnabled());

        config(['promotions.online_payments' => true]);
        $this->assertTrue(app(PromotionCheckout::class)->isEnabled());
    }

    public function test_addons_included_in_package_are_dropped(): void
    {
        $pricing = new PromotionPricing;

        $this->assertSame(['rush'], $pricing->sanitizeAddons('spotlight', ['youtube_video', 'rush', 'rush', 'nope']));
        $this->assertSame(39900 + 3900, $pricing->total('spotlight', ['youtube_video', 'rush']));
    }

    public function test_store_requires_rights_and_terms(): void
    {
        $this->mockCheckoutSession();

        $this->post('/promote/checkout', $this->validPayload(['rights_confirmed' => null, 'terms' => null]))
            ->assertSessionHasErrors(['rights_confirmed', 'terms']);

        $this->assertDatabaseCount('promotion_requests', 0);
    }

    public function test_webhook_marks_order_paid_once_and_sends_emails(): void
    {
        Mail::fake();
        $promotion = $this->makePromotion();

        $session = [
            'id' => 'cs_test_1',
            'payment_status' => 'paid',
            'amount_total' => 7900,
            'payment_intent' => 'pi_123',
            'metadata' => ['promotion_reference' => $promotion->reference],
        ];

        $this->postWebhook($session)->assertOk();
        $this->postWebhook($session)->assertOk();

        $promotion->refresh();
        $this->assertSame('paid', $promotion->status);
        $this->assertSame('pi_123', $promotion->stripe_payment_intent);
        $this->assertNotNull($promotion->paid_at);

        Mail::assertSent(PromotionReceipt::class, 1);
        Mail::assertSent(PromotionAdminAlert::class, fn ($mail) => $mail->hasTo('team@example.com'));
        Mail::assertSent(PromotionAdminAlert::class, 1);
    }

    public function test_webhook_ignores_amount_mismatch(): void
    {
        Mail::fake();
        $promotion = $this->makePromotion();

        $this->postWebhook([
            'id' => 'cs_test_2',
            'payment_status' => 'paid',
            'amount_total' => 100,
            'metadata' => ['promotion_reference' => $promotion->reference],
        ])->assertOk();

        $this->assertSame('pending_payment', $promotion->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_webhook_rejects_bad_signature(): void
    {
        $this->call('POST', '/stripe/webhook', [], [], [], [
            'HTTP_STRIPE_SIGNATURE' => 't=1,v1=bad',
        ], '{}')->assertStatus(400);
    }

    public function test_thanks_page_requires_matching_session(): void
    {
        $promotion = $this->makePromotion();
        $promotion->forceFill(['stripe_session_id' => 'cs_real', 'paid_at' => now(), 'status' => 'paid'])->save();

        $this->get(route('promote.thanks', $promotion).'?session_id=cs_guess')->assertNotFound();
        $this->get(route('promote.thanks', $promotion).'?session_id=cs_real')->assertOk()->assertSee('Thank you');
    }

    public function test_pay_page_requires_signature(): void
    {
        $promotion = $this->makePromotion();

        $this->get(route('promote.pay', $promotion))->assertForbidden();
        $this->get($promotion->payUrl())->assertOk()->assertSee($promotion->reference);
    }

    public function test_sponsored_post_marks_only_outbound_links(): void
    {
        config(['app.url' => 'https://toppingafrica.com']);

        $post = new Post([
            'content' => '<a href="https://youtube.com/x">yt</a> <a href="https://toppingafrica.com/a">in</a> <a href="https://open.spotify.com/y" rel="nofollow">sp</a>',
            'is_sponsored' => true,
        ]);

        $html = $post->rendered_content;
        $this->assertStringContainsString('<a href="https://youtube.com/x" rel="sponsored noopener">', $html);
        $this->assertStringContainsString('<a href="https://toppingafrica.com/a">', $html);
        $this->assertStringContainsString('rel="nofollow sponsored noopener"', $html);

        $post->is_sponsored = false;
        $this->assertSame($post->content, $post->rendered_content);
    }

    public function test_admin_marking_live_emails_buyer_once_and_flags_post_sponsored(): void
    {
        Mail::fake();
        $staff = User::factory()->create(['is_staff' => true]);
        $post = Post::create(['author_id' => $staff->id, 'title' => 'Ama drops new single', 'content' => '<p>x</p>', 'status' => 'published', 'post_type' => 'article']);
        $promotion = $this->makePromotion(['status' => 'in_production']);

        $component = Livewire::actingAs($staff)
            ->test(ManagePromotions::class)
            ->call('open', $promotion->reference)
            ->set('status', 'live')
            ->set('postLookup', (string) $post->id)
            ->set('deliverables.facebook_post', 'https://facebook.com/toppingafrica/posts/1')
            ->call('save')
            ->assertHasNoErrors();

        $component->call('save');

        $promotion->refresh();
        $this->assertSame('live', $promotion->status);
        $this->assertSame($post->id, $promotion->post_id);
        $this->assertNotNull($promotion->live_at);
        $this->assertTrue($post->fresh()->is_sponsored);
        Mail::assertSent(PromotionLive::class, 1);
    }

    public function test_admin_can_create_sponsored_draft_from_order(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $promotion = $this->makePromotion(['status' => 'paid']);

        Livewire::actingAs($staff)
            ->test(ManagePromotions::class)
            ->call('open', $promotion->reference)
            ->call('createDraftPost')
            ->assertRedirect();

        $post = Post::sole();
        $this->assertTrue($post->is_sponsored);
        $this->assertSame('draft', $post->status);
        $this->assertSame('in_production', $promotion->fresh()->status);
        $this->assertSame($post->id, $promotion->fresh()->post_id);
    }

    public function test_admin_marking_invoice_paid_records_payment_date(): void
    {
        $staff = User::factory()->create(['is_staff' => true]);
        $promotion = $this->makePromotion(['status' => 'invoiced']);

        Livewire::actingAs($staff)
            ->test(ManagePromotions::class)
            ->assertSee('New inquiries')
            ->call('open', $promotion->reference)
            ->set('status', 'paid')
            ->call('save')
            ->assertHasNoErrors();

        $promotion->refresh();
        $this->assertSame('paid', $promotion->status);
        $this->assertNotNull($promotion->paid_at);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

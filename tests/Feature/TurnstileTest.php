<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Blog\Comments;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\TurnstileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class TurnstileTest extends TestCase
{
    use RefreshDatabase;

    private function service(): TurnstileService
    {
        return new TurnstileService(siteKey: 'site', secretKey: 'secret', enabled: true);
    }

    private function fakeSiteverify(array $body): void
    {
        Http::fake(['challenges.cloudflare.com/*' => Http::response($body)]);
    }

    public function test_valid_token_with_matching_action_passes(): void
    {
        $this->fakeSiteverify(['success' => true, 'action' => 'comment']);

        $this->assertTrue($this->service()->verify('token', 'comment', '1.2.3.4')['success']);

        Http::assertSent(fn ($request) => $request['secret'] === 'secret'
            && $request['response'] === 'token'
            && $request['remoteip'] === '1.2.3.4');
    }

    public function test_rejected_token_fails(): void
    {
        $this->fakeSiteverify(['success' => false, 'error-codes' => ['invalid-input-response']]);

        $this->assertFalse($this->service()->verify('token', 'comment')['success']);
    }

    public function test_token_minted_for_another_action_fails(): void
    {
        $this->fakeSiteverify(['success' => true, 'action' => 'newsletter_subscribe']);

        $this->assertFalse($this->service()->verify('token', 'login')['success']);
    }

    public function test_empty_and_legacy_bypass_tokens_fail_without_calling_cloudflare(): void
    {
        $this->fakeSiteverify(['success' => false]);

        $this->assertFalse($this->service()->verify('', 'comment')['success']);
        $this->assertFalse($this->service()->verify('RECAPTCHA_FAILED', 'comment')['success']);

        Http::assertSentCount(1); // only the non-empty legacy token reached Cloudflare
    }

    public function test_cloudflare_outage_fails_open(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->assertTrue($this->service()->verify('token', 'comment')['success']);
    }

    public function test_disabled_or_unconfigured_service_skips_verification(): void
    {
        Http::fake();

        $this->assertTrue((new TurnstileService('site', 'secret', false))->verify('', 'comment')['success']);
        $this->assertTrue((new TurnstileService(null, null, true))->verify('', 'comment')['success']);
        Http::assertNothingSent();
    }

    public function test_comment_is_blocked_when_turnstile_fails(): void
    {
        config(['turnstile.enabled' => true, 'turnstile.site_key' => 'site', 'turnstile.secret_key' => 'secret']);
        $this->app->forgetInstance(TurnstileService::class);
        $this->fakeSiteverify(['success' => false]);

        $post = Post::factory()->create(['status' => 'published']);

        Livewire::actingAs(User::factory()->create())
            ->test(Comments::class, ['postId' => $post->id])
            ->set('body', 'Buy cheap stuff here')
            ->set('turnstileToken', 'bad-token')
            ->call('submitComment')
            ->assertHasErrors('turnstile')
            ->assertDispatched('turnstile-reset');

        $this->assertSame(0, Comment::count());
    }

    public function test_comments_are_rate_limited_per_user(): void
    {
        $post = Post::factory()->create(['status' => 'published']);
        $component = Livewire::actingAs(User::factory()->create())
            ->test(Comments::class, ['postId' => $post->id]);

        for ($i = 1; $i <= 5; $i++) {
            $component->set('body', "Genuine comment number {$i}")->call('submitComment')->assertHasNoErrors();
        }

        $component->set('body', 'One too many')->call('submitComment')->assertHasErrors('body');

        $this->assertSame(5, Comment::count());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Notifications\AgentImageAttachmentFailed;
use App\Services\Blog\FeaturedImageService;
use App\Services\ImageSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class FeaturedImageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_calls_reachability_check_on_every_candidate_when_all_fail(): void
    {
        Notification::fake();

        $author = User::factory()->create(['is_super_admin' => true]);
        $post = Post::factory()->create(['author_id' => $author->id]);

        // 4 Google candidates, all unreachable. The old code would only try
        // the first one then bail; the new code should try every one before
        // giving up and notifying.
        $this->bindImageSearchMock([
            'google' => [
                ['url' => 'https://bad1.example.com/img.jpg', 'width' => 1200, 'height' => 800],
                ['url' => 'https://bad2.example.com/img.jpg', 'width' => 1200, 'height' => 800],
                ['url' => 'https://bad3.example.com/img.jpg', 'width' => 1200, 'height' => 800],
                ['url' => 'https://bad4.example.com/img.jpg', 'width' => 1200, 'height' => 800],
            ],
            'pexels' => [],
        ]);

        $attempts = 0;
        Http::fake(function () use (&$attempts) {
            $attempts++;

            return Http::response('', 404);
        });

        $service = app(FeaturedImageService::class);
        $url = $service->attach($post, 'African tech startup');

        $this->assertNull($url);
        $this->assertGreaterThanOrEqual(
            4,
            $attempts,
            'Service should hit reachability check at least once per candidate (HEAD+GET ≥ 4 calls)'
        );
        Notification::assertSentTo([$author], AgentImageAttachmentFailed::class);
    }

    public function test_notifies_admins_when_all_sources_exhausted(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $staff = User::factory()->create(['is_staff' => true]);
        $regularUser = User::factory()->create([]);

        $post = Post::factory()->create(['author_id' => $superAdmin->id]);

        // No library media, no Google results, no Pexels results.
        $this->bindImageSearchMock(['google' => [], 'pexels' => []]);

        $service = app(FeaturedImageService::class);
        $url = $service->attach($post, 'Some unmatchable query xyzzy');

        $this->assertNull($url);

        Notification::assertSentTo([$superAdmin, $staff], AgentImageAttachmentFailed::class);
        Notification::assertNotSentTo([$regularUser], AgentImageAttachmentFailed::class);
    }

    public function test_skips_when_post_already_has_featured_image_and_not_forced(): void
    {
        Notification::fake();
        $this->bindImageSearchMock(['google' => [], 'pexels' => []]);

        $author = User::factory()->create();
        $post = Post::factory()->create(['author_id' => $author->id]);

        // Seed an existing featured image.
        $tempPath = tempnam(sys_get_temp_dir(), 'feat_').'.jpg';
        file_put_contents($tempPath, base64_decode(
            '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAMCAgMCAgMDAwMEAwMEBQgFBQQEBQoHBwYIDAoMDAsKCwsNDhIQDQ4RDgsLEBYQERMUFRUVDA8XGBYUGBIUFRT/2wBDAQMEBAUEBQkFBQkUDQsNFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBQUFBT/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AL+AB//Z'
        ));
        $post->addMedia($tempPath)->toMediaCollection('featured_image');

        $service = app(FeaturedImageService::class);
        $url = $service->attach($post, 'anything');

        $this->assertNull($url);
        Notification::assertNothingSent();
    }

    private function bindImageSearchMock(array $payload): void
    {
        // ImageSearchService is final — bind a stub via the container that
        // overrides resolution from the same class name.
        $this->app->bind(ImageSearchService::class, fn () => new class($payload) extends ImageSearchService
        {
            public function __construct(private readonly array $payload) {}

            public function searchGoogle(string $query, int $start = 1): array
            {
                return [
                    'results' => $this->payload['google'] ?? [],
                    'total_results' => count($this->payload['google'] ?? []),
                    'start' => $start,
                ];
            }

            public function searchPexels(string $query, int $page = 1, int $perPage = 20): array
            {
                return [
                    'results' => $this->payload['pexels'] ?? [],
                    'total_results' => count($this->payload['pexels'] ?? []),
                    'page' => $page,
                    'per_page' => $perPage,
                ];
            }
        });
    }
}

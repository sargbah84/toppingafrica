<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\RepullCreatorJob;
use App\Models\Creator;
use App\Services\ClaudeBioService;
use App\Services\CreatorAvatarService;
use App\Services\CreatorSocialLinkBuilder;
use App\Services\PerplexityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RepullCreatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.perplexity.key' => 'test', 'services.anthropic.key' => null]);
    }

    private function fakePerplexity(array $results): void
    {
        Http::fake([
            'api.perplexity.ai/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($results)]]],
            ]),
            '*' => Http::response([], 404),
        ]);
    }

    private function makeCreator(string $name): Creator
    {
        $creator = Creator::create([
            'name' => $name,
            'bio' => 'Original bio.',
            'country' => 'Nigeria',
            'category' => 'Comedy',
            'status' => 'published',
        ]);
        $creator->socialLinks()->create(['platform' => 'tiktok', 'url' => 'https://tiktok.com/@purplespeedy', 'handle' => 'purplespeedy']);

        return $creator;
    }

    public function test_repull_does_not_copy_a_different_creators_data(): void
    {
        $creator = $this->makeCreator('Purplespeedy');
        $this->fakePerplexity([[
            'name' => 'Layi Wasabi',
            'bio_summary' => 'Layi Wasabi is a Nigerian comedian.',
            'instagram_handle' => 'layiwasabi',
        ]]);

        (new RepullCreatorJob($creator->id))->handle(
            app(PerplexityService::class),
            app(ClaudeBioService::class),
            app(CreatorAvatarService::class),
            app(CreatorSocialLinkBuilder::class),
        );

        $creator->refresh();
        $this->assertSame('Original bio.', $creator->bio);
        $this->assertFalse($creator->socialLinks()->where('platform', 'instagram')->exists());
    }

    public function test_repull_applies_data_when_the_result_is_the_same_creator(): void
    {
        $creator = $this->makeCreator('Purplespeedy');
        $this->fakePerplexity([[
            'name' => 'Ayo Speedy',
            'bio_summary' => 'Purplespeedy is a Nigerian skit maker.',
            'instagram_handle' => 'purplespeedy',
        ]]);

        (new RepullCreatorJob($creator->id))->handle(
            app(PerplexityService::class),
            app(ClaudeBioService::class),
            app(CreatorAvatarService::class),
            app(CreatorSocialLinkBuilder::class),
        );

        $creator->refresh();
        $this->assertSame('Purplespeedy is a Nigerian skit maker.', $creator->bio);
        $this->assertSame('https://instagram.com/purplespeedy', $creator->socialLinks()->where('platform', 'instagram')->value('url'));
    }

    public function test_is_same_creator_matching(): void
    {
        $this->assertTrue(PerplexityService::isSameCreator(['name' => 'Layi  Wasabi'], 'layi wasabi'));
        $this->assertTrue(PerplexityService::isSameCreator(['name' => 'Gilles Touré'], 'Gilles Toure'));
        $this->assertTrue(PerplexityService::isSameCreator(['name' => 'X', 'tiktok_handle' => '@purplespeedy'], 'Purplespeedy'));
        $this->assertFalse(PerplexityService::isSameCreator(['name' => 'Brain Jotter'], 'Layi Wasabi'));
        $this->assertFalse(PerplexityService::isSameCreator(['name' => 'Camidoh'], 'Last'));

        $this->assertTrue(PerplexityService::isSameCreator(['name' => 'Tony Onyemaechi Elumelu'], 'Tony Elumelu'));
        $this->assertTrue(PerplexityService::isSameCreator(['name' => 'Patrick Okorie', 'instagram_handle' => 'patorankingfire'], 'Patoranking'));
        $this->assertTrue(PerplexityService::isSameCreator(['name' => 'Shirin Joy Borau'], 'Shirin'));
        $this->assertTrue(PerplexityService::isSameCreator(['name' => 'Chioma Nmakama'], 'Dr. Chioma Nmakama'));
        $this->assertFalse(PerplexityService::isSameCreator(['name' => 'Tony Ade'], 'Tony Elumelu'));
        $this->assertFalse(PerplexityService::isSameCreator(['name' => 'Ruth Kadiri'], 'Ruth'));
        $this->assertFalse(PerplexityService::isSameCreator(['name' => 'Joy Shirin'], 'Shirin'));
    }
}

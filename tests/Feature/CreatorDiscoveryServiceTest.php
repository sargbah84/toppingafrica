<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\EnrichCreatorJob;
use App\Models\Creator;
use App\Models\Setting;
use App\Services\Blog\CreatorDiscoveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class CreatorDiscoveryServiceTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CreatorDiscoveryService
    {
        return app(CreatorDiscoveryService::class);
    }

    public function test_creates_pending_creator_for_a_valid_new_name(): void
    {
        Bus::fake();

        $body = '<p>Today we profile Khaby Lame, who broke records.</p>';
        $ids = $this->service()->discover(
            [['name' => 'Khaby Lame', 'category' => 'comedy', 'country' => 'Italy']],
            $body,
        );

        $creator = Creator::where('slug', 'khaby-lame')->first();
        $this->assertNotNull($creator);
        $this->assertSame('pending', $creator->status);
        $this->assertSame([$creator->id], $ids);
        Bus::assertDispatched(EnrichCreatorJob::class, fn ($job) => $job->creatorId === $creator->id);
    }

    public function test_matches_existing_creator_without_creating_duplicate(): void
    {
        Bus::fake();

        $existing = Creator::create([
            'name' => 'Burna Boy',
            'status' => 'published',
            'bio' => 'Existing bio',
            'country' => 'Nigeria',
            'category' => 'music',
        ]);

        // Different casing/spacing — slugs identically, must match not duplicate.
        $ids = $this->service()->discover(
            [['name' => 'burna  boy']],
            '<p>burna boy news</p>',
        );

        $this->assertSame([$existing->id], $ids);
        $this->assertSame(1, Creator::where('slug', 'burna-boy')->count());
        Bus::assertNotDispatched(EnrichCreatorJob::class);
    }

    public function test_dedupes_repeated_mentions_in_one_call(): void
    {
        Bus::fake();

        $ids = $this->service()->discover(
            [
                ['name' => 'Tems'],
                ['name' => 'Tems'],
            ],
            '<p>Tems Tems Tems performed.</p>',
        );

        $this->assertCount(1, $ids);
        $this->assertSame(1, Creator::where('slug', 'tems')->count());
    }

    public function test_rejects_junk_and_denylisted_names(): void
    {
        Bus::fake();

        $ids = $this->service()->discover(
            [
                ['name' => 'sale'],        // denylisted + lowercase
                ['name' => 'last'],        // denylisted
                ['name' => 'African Tech'], // contains denylisted tokens
                ['name' => 'a'],           // too short
            ],
            '<p>Some sale last African Tech content.</p>',
        );

        $this->assertSame([], $ids);
        $this->assertSame(0, Creator::count());
    }

    public function test_single_word_name_requires_capitalized_body_appearance(): void
    {
        Bus::fake();

        // "Davido" is capitalized in the body -> accepted.
        $accepted = $this->service()->discover(
            [['name' => 'Davido']],
            '<p>Davido announced a tour.</p>',
        );
        $this->assertCount(1, $accepted);

        // "wizkid" never appears capitalized -> rejected.
        $rejected = $this->service()->discover(
            [['name' => 'wizkid']],
            '<p>the wizkid of the village.</p>',
        );
        $this->assertSame([], $rejected);
    }

    public function test_respects_max_per_post_cap(): void
    {
        Bus::fake();
        config(['blog.creator_discovery.max_per_post' => 2]);

        $ids = $this->service()->discover(
            [
                ['name' => 'Alpha One'],
                ['name' => 'Beta Two'],
                ['name' => 'Gamma Three'],
            ],
            '<p>Alpha One Beta Two Gamma Three.</p>',
        );

        $this->assertCount(2, $ids);
        $this->assertSame(2, Creator::count());
    }

    public function test_disabled_flag_short_circuits(): void
    {
        Bus::fake();
        config(['blog.creator_discovery.enabled' => false]);

        $ids = $this->service()->discover([['name' => 'Khaby Lame']], '<p>Khaby Lame</p>');

        $this->assertSame([], $ids);
        $this->assertSame(0, Creator::count());
        Bus::assertNotDispatched(EnrichCreatorJob::class);
    }

    public function test_setting_toggle_overrides_config_default(): void
    {
        Bus::fake();

        // Config says enabled, but the admin turned it OFF via the Setting —
        // the runtime Setting must win so the feature can be killed without a
        // deploy.
        config(['blog.creator_discovery.enabled' => true]);
        Setting::set('creator_discovery_enabled', false);

        $ids = $this->service()->discover([['name' => 'Khaby Lame']], '<p>Khaby Lame</p>');

        $this->assertSame([], $ids);
        $this->assertSame(0, Creator::count());

        // And flipping the Setting back on re-enables it.
        Setting::set('creator_discovery_enabled', true);
        $ids = $this->service()->discover([['name' => 'Khaby Lame']], '<p>Khaby Lame</p>');
        $this->assertCount(1, $ids);
    }
}

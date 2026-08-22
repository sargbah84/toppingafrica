<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PinnedSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pinned_to_scope_includes_indefinite_and_future_pins(): void
    {
        $indefinite = Post::factory()->published()->create([
            'pinned_section' => 'most_popular',
            'pinned_until' => null,
        ]);

        $future = Post::factory()->published()->create([
            'pinned_section' => 'most_popular',
            'pinned_until' => now()->addDay(),
        ]);

        $ids = Post::published()->pinnedTo('most_popular')->pluck('id');

        $this->assertTrue($ids->contains($indefinite->id));
        $this->assertTrue($ids->contains($future->id));
    }

    public function test_pinned_to_scope_excludes_expired_pins_and_other_sections(): void
    {
        $expired = Post::factory()->published()->create([
            'pinned_section' => 'most_popular',
            'pinned_until' => now()->subMinute(),
        ]);

        $otherSection = Post::factory()->published()->create([
            'pinned_section' => 'tv',
            'pinned_until' => null,
        ]);

        $ids = Post::published()->pinnedTo('most_popular')->pluck('id');

        $this->assertFalse($ids->contains($expired->id));
        $this->assertFalse($ids->contains($otherSection->id));
    }

    public function test_pinned_post_leads_most_popular_over_higher_viewed_post(): void
    {
        $popular = Post::factory()->published()->create(['title' => 'Organically Popular']);
        $popular->views()->create(['viewed_at' => now()->subDay(), 'ip_address' => '127.0.0.1']);
        $popular->views()->create(['viewed_at' => now()->subDay(), 'ip_address' => '127.0.0.2']);

        $pinned = Post::factory()->published()->create([
            'title' => 'Editor Pinned',
            'pinned_section' => 'most_popular',
            'pinned_until' => null,
        ]);

        $mostPopular = $this->get('/')->viewData('mostPopular');

        $this->assertSame($pinned->id, $mostPopular->first()->id);
        $this->assertCount(1, $mostPopular->where('id', $pinned->id));
    }

    public function test_expired_pin_does_not_lead_most_popular(): void
    {
        $popular = Post::factory()->published()->create(['title' => 'Organically Popular']);
        $popular->views()->create(['viewed_at' => now()->subDay(), 'ip_address' => '127.0.0.1']);

        Post::factory()->published()->create([
            'title' => 'Lapsed Pin',
            'pinned_section' => 'most_popular',
            'pinned_until' => now()->subMinute(),
        ]);

        $mostPopular = $this->get('/')->viewData('mostPopular');

        $this->assertSame($popular->id, $mostPopular->first()->id);
    }

    public function test_pins_are_capped_at_configured_slots(): void
    {
        config(['blog.pinned_sections.most_popular.slots' => 2]);

        Post::factory()->count(4)->published()->create([
            'pinned_section' => 'most_popular',
            'pinned_until' => null,
        ]);

        $organic = Post::factory()->published()->create(['title' => 'Organic']);
        $organic->views()->create(['viewed_at' => now()->subDay(), 'ip_address' => '127.0.0.1']);

        $response = $this->get('/');
        $mostPopular = $response->viewData('mostPopular');
        $trending = $response->viewData('trending');

        // Only 2 pins honoured, so the organic post still reaches Trending.
        $this->assertCount(2, $mostPopular);
        $this->assertTrue($trending->contains('id', $organic->id));
    }

    public function test_hero_pin_leads_over_newer_featured_post(): void
    {
        Post::factory()->published()->featured()->create([
            'title' => 'Newer Featured',
            'published_at' => now()->subHour(),
        ]);

        $pinned = Post::factory()->published()->create([
            'title' => 'Hero Pinned',
            'published_at' => now()->subDays(2),
            'pinned_section' => 'hero',
            'pinned_until' => null,
        ]);

        $hero = $this->get('/')->viewData('heroPost');

        $this->assertSame($pinned->id, $hero->first()->id);
    }

    public function test_hero_pin_survives_the_recency_window(): void
    {
        $stale = Post::factory()->published()->create([
            'title' => 'Stale But Pinned',
            'published_at' => now()->subDays(400),
            'pinned_section' => 'hero',
            'pinned_until' => null,
        ]);

        $hero = $this->get('/')->viewData('heroPost');

        $this->assertSame($stale->id, $hero->first()->id);
    }

    public function test_expired_hero_pin_falls_back_to_fill_chain(): void
    {
        $featured = Post::factory()->published()->featured()->create(['title' => 'Featured']);

        Post::factory()->published()->create([
            'title' => 'Lapsed Hero Pin',
            'pinned_section' => 'hero',
            'pinned_until' => now()->subMinute(),
        ]);

        $hero = $this->get('/')->viewData('heroPost');

        $this->assertSame($featured->id, $hero->first()->id);
    }

    public function test_hero_pins_cannot_fill_every_hero_slot(): void
    {
        Post::factory()->count(5)->published()->create([
            'pinned_section' => 'hero',
            'pinned_until' => null,
        ]);

        $fresh = Post::factory()->published()->create(['published_at' => now()]);

        $hero = $this->get('/')->viewData('heroPost');

        // 2 of 3 slots cap pins, so the fill chain still surfaces a fresh post.
        $this->assertCount(3, $hero);
        $this->assertTrue($hero->contains('id', $fresh->id));
    }

    public function test_hero_pinned_post_is_not_repeated_in_editors_picked(): void
    {
        Post::factory()->count(6)->published()->featured()->create();

        $pinned = Post::factory()->published()->featured()->create([
            'title' => 'Hero Pinned Featured',
            'pinned_section' => 'hero',
            'pinned_until' => null,
        ]);

        $response = $this->get('/');

        $this->assertTrue($response->viewData('heroPost')->contains('id', $pinned->id));
        $this->assertFalse($response->viewData('editorsPicked')->contains('id', $pinned->id));
    }

    public function test_post_does_not_appear_in_both_most_popular_and_trending(): void
    {
        Post::factory()->count(6)->published()->create();

        Post::factory()->published()->create([
            'pinned_section' => 'most_popular',
            'pinned_until' => null,
        ]);

        $response = $this->get('/');
        $ids = $response->viewData('mostPopular')->pluck('id')
            ->concat($response->viewData('trending')->pluck('id'));

        $this->assertSame($ids->count(), $ids->unique()->count());
    }
}

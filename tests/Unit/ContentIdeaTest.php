<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ContentIdea;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContentIdeaTest extends TestCase
{
    use RefreshDatabase;

    private function createIdea(array $overrides = []): ContentIdea
    {
        return ContentIdea::create(array_merge([
            'title' => 'Test Idea',
            'description' => 'A test content idea description.',
            'niche' => 'technology',
            'category' => 'Technology',
            'suggested_keyword' => 'test keyword',
            'seo_score' => 75,
            'search_interest' => 'high',
            'competition' => 'low',
            'relevance_score' => 8,
            'source' => 'perplexity',
            'suggested_tone' => 'professional',
            'suggested_length' => 'long',
            'suggested_post_type' => 'article',
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
            'researched_at' => now(),
        ], $overrides));
    }

    public function test_active_scope_returns_pending_and_not_expired(): void
    {
        $active = $this->createIdea();
        $dismissed = $this->createIdea(['title' => 'Dismissed', 'status' => 'dismissed']);
        $expired = $this->createIdea(['title' => 'Expired', 'expires_at' => now()->subDay()]);
        $generated = $this->createIdea(['title' => 'Generated', 'status' => 'generated']);

        $results = ContentIdea::active()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($active));
    }

    public function test_by_niche_scope_filters_correctly(): void
    {
        $this->createIdea(['niche' => 'technology']);
        $this->createIdea(['title' => 'Sports Idea', 'niche' => 'sports']);

        $results = ContentIdea::byNiche('technology')->get();

        $this->assertCount(1, $results);
        $this->assertSame('technology', $results->first()->niche);
    }

    public function test_top_rated_scope_orders_by_score_descending(): void
    {
        $this->createIdea(['title' => 'Low', 'seo_score' => 30]);
        $this->createIdea(['title' => 'High', 'seo_score' => 90]);
        $this->createIdea(['title' => 'Mid', 'seo_score' => 60]);

        $results = ContentIdea::topRated()->get();

        $this->assertSame('High', $results->first()->title);
        $this->assertSame('Low', $results->last()->title);
    }

    public function test_mark_as_generating(): void
    {
        $idea = $this->createIdea();

        $idea->markAsGenerating();

        $this->assertSame('generating', $idea->fresh()->status);
    }

    public function test_mark_as_generated_with_post_id(): void
    {
        $idea = $this->createIdea();
        $post = Post::factory()->create();

        $idea->markAsGenerated($post->id);

        $fresh = $idea->fresh();
        $this->assertSame('generated', $fresh->status);
        $this->assertSame($post->id, $fresh->generated_post_id);
    }

    public function test_dismiss(): void
    {
        $idea = $this->createIdea();

        $idea->dismiss();

        $this->assertSame('dismissed', $idea->fresh()->status);
    }

    public function test_restore_sets_status_back_to_pending(): void
    {
        $idea = $this->createIdea(['status' => 'dismissed']);

        $idea->restore();

        $this->assertSame('pending', $idea->fresh()->status);
    }

    public function test_generated_post_relationship(): void
    {
        $post = Post::factory()->create();
        $idea = $this->createIdea([
            'status' => 'generated',
            'generated_post_id' => $post->id,
        ]);

        $this->assertTrue($idea->generatedPost->is($post));
    }

    public function test_casts_are_correct(): void
    {
        $idea = $this->createIdea(['seo_score' => 75, 'relevance_score' => 8]);

        $this->assertIsInt($idea->seo_score);
        $this->assertIsInt($idea->relevance_score);
        $this->assertInstanceOf(Carbon::class, $idea->expires_at);
        $this->assertInstanceOf(Carbon::class, $idea->researched_at);
    }
}

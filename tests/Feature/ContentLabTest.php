<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Blog\ContentLab;
use App\Models\ContentIdea;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentLabTest extends TestCase
{
    use RefreshDatabase;

    private function createStaffUser(): User
    {
        return User::factory()->create(['is_staff' => true]);
    }

    private function createIdea(array $overrides = []): ContentIdea
    {
        return ContentIdea::create(array_merge([
            'title' => 'Test Content Idea',
            'description' => 'A compelling article about African tech.',
            'niche' => 'technology',
            'category' => 'Technology',
            'suggested_keyword' => 'african tech',
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

    public function test_content_lab_page_requires_staff_access(): void
    {
        $user = User::factory()->create(['is_staff' => false, 'is_super_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.blog.content-lab'))
            ->assertForbidden();
    }

    public function test_content_lab_page_accessible_by_staff(): void
    {
        $user = $this->createStaffUser();

        $this->actingAs($user)
            ->get(route('admin.blog.content-lab'))
            ->assertOk()
            ->assertSeeLivewire(ContentLab::class);
    }

    public function test_component_renders_with_empty_state(): void
    {
        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->assertStatus(200)
            ->assertSee('Research Now')
            ->assertSee('No content ideas found');
    }

    public function test_component_displays_ideas(): void
    {
        $idea = $this->createIdea();

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->assertSee($idea->title)
            ->assertSee($idea->suggested_keyword);
    }

    public function test_search_filters_ideas(): void
    {
        $this->createIdea(['title' => 'African Startups Rising']);
        $this->createIdea(['title' => 'Nigerian Music Awards']);

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->set('search', 'Startups')
            ->assertSee('African Startups Rising')
            ->assertDontSee('Nigerian Music Awards');
    }

    public function test_niche_filter_works(): void
    {
        $this->createIdea(['title' => 'Tech Idea', 'niche' => 'technology']);
        $this->createIdea(['title' => 'Sports Idea', 'niche' => 'sports']);

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->set('nicheFilter', 'technology')
            ->assertSee('Tech Idea')
            ->assertDontSee('Sports Idea');
    }

    public function test_status_filter_pending(): void
    {
        $this->createIdea(['title' => 'Pending Idea', 'status' => 'pending']);
        $this->createIdea(['title' => 'Dismissed Idea', 'status' => 'dismissed']);

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->set('filter', 'pending')
            ->assertSee('Pending Idea')
            ->assertDontSee('Dismissed Idea');
    }

    public function test_status_filter_dismissed(): void
    {
        $this->createIdea(['title' => 'Pending Idea', 'status' => 'pending']);
        $this->createIdea(['title' => 'Dismissed Idea', 'status' => 'dismissed']);

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->set('filter', 'dismissed')
            ->assertSee('Dismissed Idea')
            ->assertDontSee('Pending Idea');
    }

    public function test_dismiss_idea(): void
    {
        $idea = $this->createIdea();

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->call('dismiss', $idea->id);

        $this->assertSame('dismissed', $idea->fresh()->status);
    }

    public function test_restore_idea(): void
    {
        $idea = $this->createIdea(['status' => 'dismissed']);

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->call('restoreIdea', $idea->id);

        $this->assertSame('pending', $idea->fresh()->status);
    }

    public function test_delete_idea(): void
    {
        $idea = $this->createIdea();

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->call('delete', $idea->id);

        $this->assertDatabaseMissing('content_ideas', ['id' => $idea->id]);
    }

    public function test_cleanup_removes_expired_and_dismissed(): void
    {
        $this->createIdea(['title' => 'Active', 'status' => 'pending']);
        $this->createIdea(['title' => 'Dismissed', 'status' => 'dismissed']);
        $this->createIdea(['title' => 'Expired', 'status' => 'pending', 'expires_at' => now()->subDay()]);
        $this->createIdea(['title' => 'Generated', 'status' => 'generated', 'expires_at' => now()->subDay()]);

        Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class)
            ->call('cleanup');

        $this->assertDatabaseHas('content_ideas', ['title' => 'Active']);
        $this->assertDatabaseHas('content_ideas', ['title' => 'Generated']);
        $this->assertDatabaseMissing('content_ideas', ['title' => 'Dismissed']);
        $this->assertDatabaseMissing('content_ideas', ['title' => 'Expired']);
    }

    public function test_counts_are_accurate(): void
    {
        $this->createIdea(['title' => 'Pending 1', 'status' => 'pending']);
        $this->createIdea(['title' => 'Pending 2', 'status' => 'pending']);
        $this->createIdea(['title' => 'Generated', 'status' => 'generated']);
        $this->createIdea(['title' => 'Dismissed', 'status' => 'dismissed']);

        $component = Livewire::actingAs($this->createStaffUser())
            ->test(ContentLab::class);

        $this->assertSame(4, $component->viewData('counts')['all']);
        $this->assertSame(2, $component->viewData('counts')['pending']);
        $this->assertSame(1, $component->viewData('counts')['generated']);
        $this->assertSame(1, $component->viewData('counts')['dismissed']);
    }

    public function test_route_is_admin_blog_lib(): void
    {
        $this->assertSame(
            url('/admin/blog/lib'),
            route('admin.blog.content-lab')
        );
    }
}

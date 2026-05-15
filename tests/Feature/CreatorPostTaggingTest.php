<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Blog\PostEditor;
use App\Models\Creator;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;

class CreatorPostTaggingTest extends TestCase
{
    use RefreshDatabase;

    private function makeCreator(array $attrs = []): Creator
    {
        return Creator::create(array_merge([
            'name' => 'Test Creator',
            'bio' => 'A test creator profile.',
            'country' => 'Nigeria',
            'category' => 'Music',
            'status' => 'published',
        ], $attrs));
    }

    public function test_post_editor_syncs_selected_creators_on_save(): void
    {
        $user = User::factory()->create(['is_staff' => true]);
        $post = Post::factory()->for($user, 'author')->create();
        $creator = $this->makeCreator(['name' => 'Burna Boy', 'slug' => 'burna-boy']);

        Livewire::actingAs($user)
            ->test(PostEditor::class, ['postId' => $post->id])
            ->set('selectedCreators', [[
                'id' => $creator->id,
                'name' => $creator->name,
                'slug' => $creator->slug,
                'country' => $creator->country,
                'category' => $creator->category,
                'is_featured' => false,
                'is_trending' => false,
            ]])
            ->call('saveDraft')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('creator_post', [
            'post_id' => $post->id,
            'creator_id' => $creator->id,
        ]);
    }

    public function test_creator_profile_page_lists_featured_posts(): void
    {
        $creator = $this->makeCreator(['name' => 'Tyla', 'slug' => 'tyla']);
        $post = Post::factory()->published()->create(['title' => 'Tyla wins big']);
        $unrelated = Post::factory()->published()->create(['title' => 'Unrelated news']);

        $post->creators()->attach($creator);

        $this->get("/creators/{$creator->slug}")
            ->assertOk()
            ->assertSee('Articles featuring Tyla')
            ->assertSee('Tyla wins big')
            ->assertDontSee('Unrelated news');
    }

    public function test_creator_profile_hides_featured_section_when_no_posts(): void
    {
        $creator = $this->makeCreator(['name' => 'Solo Creator', 'slug' => 'solo']);

        $this->get("/creators/{$creator->slug}")
            ->assertOk()
            ->assertDontSee('Articles featuring');
    }

    public function test_backfill_command_dry_run_does_not_write(): void
    {
        $creator = $this->makeCreator(['name' => 'Davido', 'slug' => 'davido']);
        Post::factory()->published()->create([
            'title' => 'Davido drops new album',
            'content' => '<p>Davido has released. Davido performed. Davido fans rejoice.</p>',
        ]);

        Artisan::call('creators:backfill-posts');

        $this->assertDatabaseCount('creator_post', 0);
    }

    public function test_backfill_command_apply_attaches_via_title_and_body_signals(): void
    {
        $creator = $this->makeCreator(['name' => 'Davido', 'slug' => 'davido']);
        $post = Post::factory()->published()->create([
            'title' => 'Davido drops new album',
            'content' => '<p>Davido has released. Davido performed. Davido fans rejoice.</p>',
        ]);

        Artisan::call('creators:backfill-posts', ['--apply' => true]);

        $this->assertDatabaseHas('creator_post', [
            'post_id' => $post->id,
            'creator_id' => $creator->id,
        ]);
    }

    public function test_backfill_command_apply_attaches_via_internal_link_signal(): void
    {
        $creator = $this->makeCreator(['name' => 'Yo', 'slug' => 'yo-creator']);
        $post = Post::factory()->published()->create([
            'title' => 'Music news roundup',
            'content' => '<p>Read about <a href="/creators/yo-creator">them</a>.</p>',
        ]);

        Artisan::call('creators:backfill-posts', ['--apply' => true]);

        $this->assertDatabaseHas('creator_post', [
            'post_id' => $post->id,
            'creator_id' => $creator->id,
        ]);
    }

    public function test_backfill_skips_short_names_for_body_and_title_signals(): void
    {
        // Name 'Yo' is 2 chars — under default min-name-length of 4. No link, no tag match.
        $this->makeCreator(['name' => 'Yo', 'slug' => 'yo']);
        Post::factory()->published()->create([
            'title' => 'Yo Yo Yo something else',
            'content' => '<p>Yo Yo Yo Yo Yo</p>',
        ]);

        Artisan::call('creators:backfill-posts', ['--apply' => true]);

        $this->assertDatabaseCount('creator_post', 0);
    }

    public function test_backfill_is_idempotent(): void
    {
        $creator = $this->makeCreator(['name' => 'Davido', 'slug' => 'davido']);
        Post::factory()->published()->create([
            'title' => 'Davido drops album',
            'content' => '<p>Davido Davido Davido</p>',
        ]);

        Artisan::call('creators:backfill-posts', ['--apply' => true]);
        Artisan::call('creators:backfill-posts', ['--apply' => true]);

        $this->assertDatabaseCount('creator_post', 1);
    }
}

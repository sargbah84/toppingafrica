<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Blog\PostReactions;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PostReactionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_component_renders_for_guest(): void
    {
        $post = Post::factory()->published()->create();

        Livewire::test(PostReactions::class, ['post' => $post])
            ->assertStatus(200)
            ->assertSee('React:');
    }

    public function test_component_renders_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(PostReactions::class, ['post' => $post])
            ->assertStatus(200)
            ->assertSee('React:');
    }

    public function test_authenticated_user_can_react(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(PostReactions::class, ['post' => $post])
            ->call('react', 'fire')
            ->assertSet('userReaction', 'fire');

        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => 'fire',
        ]);
    }

    public function test_guest_cannot_react(): void
    {
        $post = Post::factory()->published()->create();

        Livewire::test(PostReactions::class, ['post' => $post])
            ->call('react', 'fire')
            ->assertSet('userReaction', null);

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_user_can_toggle_reaction_off(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(PostReactions::class, ['post' => $post])
            ->call('react', 'fire')
            ->assertSet('userReaction', 'fire')
            ->call('react', 'fire')
            ->assertSet('userReaction', null);

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_user_can_switch_reaction(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(PostReactions::class, ['post' => $post])
            ->call('react', 'fire')
            ->assertSet('userReaction', 'fire')
            ->call('react', 'love')
            ->assertSet('userReaction', 'love');

        $this->assertDatabaseCount('reactions', 1);
        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => 'love',
        ]);
    }

    public function test_counts_are_accurate(): void
    {
        $post = Post::factory()->published()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        Reaction::toggle($post, $user1->id, 'fire', '127.0.0.1');
        Reaction::toggle($post, $user2->id, 'fire', '127.0.0.2');
        Reaction::toggle($post, $user3->id, 'love', '127.0.0.3');

        $component = Livewire::actingAs($user1)
            ->test(PostReactions::class, ['post' => $post]);

        $counts = $component->get('counts');

        $this->assertSame(2, $counts['fire']);
        $this->assertSame(1, $counts['love']);
        $this->assertSame(0, $counts['clap']);
        $this->assertSame(3, $component->get('totalReactions'));
    }

    public function test_existing_user_reaction_is_loaded_on_mount(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        Reaction::toggle($post, $user->id, 'wow', '127.0.0.1');

        Livewire::actingAs($user)
            ->test(PostReactions::class, ['post' => $post])
            ->assertSet('userReaction', 'wow');
    }

    public function test_invalid_reaction_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();

        Livewire::actingAs($user)
            ->test(PostReactions::class, ['post' => $post])
            ->call('react', 'invalid_emoji')
            ->assertSet('userReaction', null);

        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_reaction_bar_appears_on_blog_post(): void
    {
        $post = Post::factory()->published()->create();

        $response = $this->get(route('blog.show', $post->slug));

        $response->assertStatus(200);
        $response->assertSeeLivewire(PostReactions::class);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_creates_a_reaction(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $result = Reaction::toggle($post, $user->id, 'fire', '127.0.0.1');

        $this->assertSame('fire', $result);
        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => 'fire',
        ]);
    }

    public function test_toggle_removes_reaction_when_same_type_clicked_again(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Reaction::toggle($post, $user->id, 'fire', '127.0.0.1');
        $result = Reaction::toggle($post, $user->id, 'fire', '127.0.0.1');

        $this->assertNull($result);
        $this->assertDatabaseMissing('reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_toggle_switches_reaction_when_different_type_clicked(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Reaction::toggle($post, $user->id, 'fire', '127.0.0.1');
        $result = Reaction::toggle($post, $user->id, 'love', '127.0.0.1');

        $this->assertSame('love', $result);
        $this->assertDatabaseHas('reactions', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => 'love',
        ]);
        $this->assertDatabaseCount('reactions', 1);
    }

    public function test_toggle_rejects_invalid_reaction_type(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $result = Reaction::toggle($post, $user->id, 'invalid_type', '127.0.0.1');

        $this->assertNull($result);
        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_unique_constraint_allows_one_reaction_per_user_per_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Reaction::toggle($post, $user->id, 'fire', '127.0.0.1');

        $this->assertDatabaseCount('reactions', 1);

        // Toggle to a different type should still be 1 row
        Reaction::toggle($post, $user->id, 'love', '127.0.0.1');

        $this->assertDatabaseCount('reactions', 1);
    }

    public function test_different_users_can_react_to_same_post(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $post = Post::factory()->create();

        Reaction::toggle($post, $user1->id, 'fire', '127.0.0.1');
        Reaction::toggle($post, $user2->id, 'love', '127.0.0.2');

        $this->assertDatabaseCount('reactions', 2);
    }

    public function test_same_user_can_react_to_different_posts(): void
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();

        Reaction::toggle($post1, $user->id, 'fire', '127.0.0.1');
        Reaction::toggle($post2, $user->id, 'love', '127.0.0.1');

        $this->assertDatabaseCount('reactions', 2);
    }

    public function test_ip_hash_is_stored(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        Reaction::toggle($post, $user->id, 'fire', '192.168.1.1');

        $reaction = Reaction::first();
        $expectedHash = hash('sha256', '192.168.1.1'.config('app.key'));

        $this->assertSame($expectedHash, $reaction->ip_hash);
    }

    public function test_post_reactions_relationship(): void
    {
        $post = Post::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Reaction::toggle($post, $user1->id, 'fire', '127.0.0.1');
        Reaction::toggle($post, $user2->id, 'clap', '127.0.0.2');

        $this->assertCount(2, $post->reactions);
    }
}

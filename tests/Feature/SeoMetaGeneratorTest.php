<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Admin\Blog\PostEditor;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class SeoMetaGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private function fakeOpenAi(array $payload): void
    {
        config(['blog.ai.providers.openai.api_key' => 'test-key']);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode($payload)]]],
                'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 10, 'total_tokens' => 20],
            ]),
        ]);
    }

    public function test_generate_seo_meta_fills_fields_within_limits(): void
    {
        $this->fakeOpenAi([
            'meta_title' => 'Kat Lincoln Releases New Album Out The Box',
            'meta_description' => str_repeat('a', 300),
            'focus_keyword' => 'Kat Lincoln new album',
        ]);

        $user = User::factory()->create(['is_staff' => true]);
        $post = Post::factory()->for($user, 'author')->create([
            'title' => 'Kat Lincoln Releases New Album',
            'content' => '<p>'.str_repeat('Kat Lincoln has released a new album. ', 10).'</p>',
            'meta_title' => null,
            'meta_description' => null,
            'focus_keyword' => null,
        ]);

        $component = Livewire::actingAs($user)
            ->test(PostEditor::class, ['postId' => $post->id])
            ->call('generateSeoMeta')
            ->assertSet('meta_title', 'Kat Lincoln Releases New Album Out The Box')
            ->assertSet('focus_keyword', 'Kat Lincoln new album')
            ->assertDispatched('notify', type: 'success');

        $this->assertSame(160, mb_strlen($component->get('meta_description')));
    }

    public function test_generate_tags_merges_without_duplicates_and_reuses_existing_spelling(): void
    {
        $this->fakeOpenAi(['tags' => ['Kat Lincoln', '#Afrobeats', 'liberia', 'kat lincoln', 'New Album']]);

        Tag::create(['name' => 'Liberia', 'slug' => 'liberia']);

        $user = User::factory()->create(['is_staff' => true]);
        $post = Post::factory()->for($user, 'author')->create([
            'title' => 'Kat Lincoln Releases New Album',
            'content' => '<p>'.str_repeat('Kat Lincoln has released a new album. ', 10).'</p>',
        ]);

        Livewire::actingAs($user)
            ->test(PostEditor::class, ['postId' => $post->id])
            ->set('selectedTags', ['Music', 'Kat Lincoln'])
            ->call('generateTags')
            ->assertSet('selectedTags', ['Music', 'Kat Lincoln', 'Afrobeats', 'Liberia', 'New Album'])
            ->assertDispatched('notify', type: 'success');
    }

    public function test_generate_seo_meta_requires_content(): void
    {
        Http::fake();

        $user = User::factory()->create(['is_staff' => true]);
        $post = Post::factory()->for($user, 'author')->create(['content' => '<p>Short</p>']);

        Livewire::actingAs($user)
            ->test(PostEditor::class, ['postId' => $post->id])
            ->call('generateSeoMeta')
            ->assertDispatched('notify', type: 'error');

        Http::assertNothingSent();
    }
}

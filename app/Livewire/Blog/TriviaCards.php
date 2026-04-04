<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Models\Post;
use Illuminate\View\View;
use Livewire\Component;

class TriviaCards extends Component
{
    public Post $post;
    public array $facts = [];
    public string $sourceUrl = '';

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->facts = $post->type_data['facts'] ?? [];
        $this->sourceUrl = $post->type_data['source_url'] ?? '';
    }

    public function render(): View
    {
        return view('livewire.blog.trivia-cards');
    }
}

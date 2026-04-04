<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Models\Post;
use Illuminate\View\View;
use Livewire\Component;

class QuizPlayer extends Component
{
    public Post $post;
    public array $questions = [];
    public int $passingScore = 70;
    public bool $showAnswers = true;

    public function mount(Post $post): void
    {
        $this->post = $post;
        $this->questions = $post->type_data['questions'] ?? [];
        $this->passingScore = $post->type_data['passing_score'] ?? 70;
        $this->showAnswers = $post->type_data['show_answers'] ?? true;
    }

    public function render(): View
    {
        return view('livewire.blog.quiz-player');
    }
}

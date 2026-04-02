<?php

declare(strict_types=1);

namespace App\Livewire\Blog;

use App\Models\Comment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Comments extends Component
{
    public int $postId;

    // Comment form
    public string $name = '';
    public string $email = '';
    public string $body = '';
    public string $honeypot = '';

    // Reply form
    public ?int $replyingTo = null;
    public string $replyName = '';
    public string $replyEmail = '';
    public string $replyBody = '';
    public string $replyHoneypot = '';

    // Pagination
    public int $perPage = 10;
    public int $page = 1;

    // Flash messages
    public string $successMessage = '';

    public function mount(int $postId): void
    {
        $this->postId = $postId;

        if (Auth::check()) {
            $user = Auth::user();
            $this->name = $user->name;
            $this->email = $user->email;
            $this->replyName = $user->name;
            $this->replyEmail = $user->email;
        }
    }

    public function submitComment(): void
    {
        if ($this->honeypot !== '') {
            return;
        }

        $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'body' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        Comment::create([
            'post_id' => $this->postId,
            'user_id' => Auth::id(),
            'guest_name' => Auth::check() ? null : $this->name,
            'guest_email' => Auth::check() ? null : $this->email,
            'body' => $this->body,
            'status' => 'pending',
            'ip_address' => request()->ip(),
        ]);

        $this->body = '';
        $this->successMessage = 'Your comment is awaiting moderation. Thank you!';

        $this->dispatch('comment-submitted');
    }

    public function submitReply(): void
    {
        if ($this->replyHoneypot !== '' || $this->replyingTo === null) {
            return;
        }

        $this->validate([
            'replyName' => ['required', 'string', 'min:2', 'max:100'],
            'replyEmail' => ['required', 'email', 'max:255'],
            'replyBody' => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        // Ensure max 2 levels: if parent is already a reply, use its parent_id
        $parent = Comment::findOrFail($this->replyingTo);
        $parentId = $parent->parent_id ?? $parent->id;

        Comment::create([
            'post_id' => $this->postId,
            'user_id' => Auth::id(),
            'parent_id' => $parentId,
            'guest_name' => Auth::check() ? null : $this->replyName,
            'guest_email' => Auth::check() ? null : $this->replyEmail,
            'body' => $this->replyBody,
            'status' => 'pending',
            'ip_address' => request()->ip(),
        ]);

        $this->replyBody = '';
        $this->replyingTo = null;
        $this->successMessage = 'Your reply is awaiting moderation. Thank you!';
    }

    public function startReply(int $commentId): void
    {
        $this->replyingTo = $commentId;
        $this->replyBody = '';

        if (Auth::check()) {
            $user = Auth::user();
            $this->replyName = $user->name;
            $this->replyEmail = $user->email;
        }
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
        $this->replyBody = '';
    }

    public function loadMore(): void
    {
        $this->page++;
    }

    #[Computed]
    public function comments(): Collection
    {
        return Comment::where('post_id', $this->postId)
            ->approved()
            ->topLevel()
            ->with(['replies' => function ($query): void {
                $query->approved()->oldest();
            }, 'user', 'replies.user'])
            ->latest()
            ->take($this->page * $this->perPage)
            ->get();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Comment::where('post_id', $this->postId)
            ->approved()
            ->count();
    }

    #[Computed]
    public function hasMore(): bool
    {
        $topLevelCount = Comment::where('post_id', $this->postId)
            ->approved()
            ->topLevel()
            ->count();

        return $topLevelCount > ($this->page * $this->perPage);
    }

    public function render(): View
    {
        return view('livewire.blog.comments');
    }
}

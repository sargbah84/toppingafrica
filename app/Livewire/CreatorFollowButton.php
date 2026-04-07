<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Creator;
use Livewire\Component;

class CreatorFollowButton extends Component
{
    public Creator $creator;
    public string $variant = 'heart'; // 'heart' or 'pill'
    public bool $following = false;
    public int $count = 0;

    public function mount(Creator $creator, string $variant = 'heart'): void
    {
        $this->creator = $creator;
        $this->variant = $variant;
        $this->refreshState();
    }

    public function toggle(): void
    {
        if (! auth()->check()) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        if ($this->creator->isFollowedBy(auth()->user())) {
            $this->creator->followers()->detach(auth()->id());
        } else {
            $this->creator->followers()->syncWithoutDetaching([auth()->id()]);
        }

        $this->refreshState();
    }

    protected function refreshState(): void
    {
        $this->following = $this->creator->isFollowedBy(auth()->user());
        $this->count = $this->creator->followers()->count();
    }

    public function render()
    {
        return view('livewire.creator-follow-button');
    }
}

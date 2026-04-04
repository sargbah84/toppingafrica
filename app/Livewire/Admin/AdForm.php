<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Ad;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AdForm extends Component
{
    public ?Ad $ad = null;

    public string $name = '';
    public string $position = '';
    public ?string $code = '';
    public ?string $imageUrl = null;
    public ?string $url = '';
    public bool $is_active = true;
    public int $order = 0;
    public ?string $starts_at = null;
    public ?string $ends_at = null;

    public function mount(?Ad $ad = null): void
    {
        if ($ad?->exists) {
            $this->ad = $ad;
            $this->name = $ad->name;
            $this->position = $ad->position;
            $this->code = $ad->code ?? '';
            $this->imageUrl = $ad->image;
            $this->url = $ad->url ?? '';
            $this->is_active = $ad->is_active;
            $this->order = $ad->order;
            $this->starts_at = $ad->starts_at?->format('Y-m-d\TH:i');
            $this->ends_at = $ad->ends_at?->format('Y-m-d\TH:i');
        }
    }

    #[On('media-selected')]
    public function onMediaSelected(string $url, string $context): void
    {
        if ($context === 'ad_image') {
            $this->imageUrl = $url;
        }
    }

    public function removeImage(): void
    {
        $this->imageUrl = null;
    }

    public function save(): mixed
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'in:header,sidebar,in_article,after_content,footer'],
            'code' => ['nullable', 'string', 'max:10000'],
            'imageUrl' => ['nullable', 'string', 'max:500'],
            'url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $data = [
            'name' => $validated['name'],
            'position' => $validated['position'],
            'code' => $validated['code'] ?: null,
            'image' => $this->imageUrl,
            'url' => $validated['url'] ?: null,
            'is_active' => $this->is_active,
            'order' => $validated['order'] ?? 0,
            'starts_at' => $validated['starts_at'] ?: null,
            'ends_at' => $validated['ends_at'] ?: null,
        ];

        if ($this->ad?->exists) {
            $this->ad->update($data);
            session()->flash('success', 'Ad updated successfully.');
        } else {
            Ad::create($data);
            session()->flash('success', 'Ad created successfully.');
        }

        return redirect()->route('admin.ads.index');
    }

    public function render(): View
    {
        return view('livewire.admin.ad-form');
    }
}

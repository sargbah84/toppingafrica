<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Creator;
use App\Services\CreatorAvatarService;
use Livewire\Component;

class CreatorQuickEdit extends Component
{
    public Creator $creator;

    // Edit state
    public bool $editing = false;
    public string $editName = '';
    public string $editBio = '';
    public string $editCategory = '';
    public string $editCountry = '';
    public ?string $photoData = null;
    public ?string $photoName = null;

    // Social links editing
    public array $editSocialLinks = [];

    // Google Images picker (Serper) — mirrors the admin avatar picker. Picking
    // attaches immediately and persists outside save(), matching the admin flow.
    public bool $showAvatarPicker = false;
    public string $avatarSearchQuery = '';
    public array $avatarCandidates = [];
    public bool $avatarSearching = false;
    public string $avatarPickerError = '';

    protected array $categories = [
        'Comedy', 'Fashion', 'Food', 'Music', 'Lifestyle',
        'Travel', 'Tech', 'Beauty', 'Sports', 'Education',
        'Finance', 'Health', 'Art', 'Gaming', 'Photography',
    ];

    protected array $africanCountries = [
        'Nigeria', 'Ghana', 'Kenya', 'South Africa', 'Tanzania',
        'Ethiopia', 'Egypt', 'Morocco', 'Senegal', 'Cameroon',
        'Ivory Coast', 'Uganda', 'Rwanda', 'Zimbabwe', 'Mozambique',
        'Angola', 'DR Congo', 'Algeria', 'Tunisia', 'Liberia',
    ];

    public function mount(Creator $creator): void
    {
        $this->creator = $creator;
    }

    public function startEditing(): void
    {
        abort_unless($this->creator->canBeEditedBy(auth()->user()), 403);

        $this->editName = $this->creator->name;
        $this->editBio = $this->creator->bio ?? '';
        $this->editCategory = $this->creator->category ?? '';
        $this->editCountry = $this->creator->country ?? '';
        $this->photoData = null;
        $this->photoName = null;
        $this->resetAvatarPicker();

        $this->editSocialLinks = $this->creator->socialLinks
            ->map(fn ($link) => [
                'id' => $link->id,
                'platform' => $link->platform,
                'url' => $link->url,
                'handle' => $link->handle ?? '',
                'follower_count' => $link->follower_count,
            ])
            ->toArray();

        $this->editing = true;
    }

    public function cancelEditing(): void
    {
        $this->editing = false;
        $this->photoData = null;
        $this->photoName = null;
        $this->resetAvatarPicker();
    }

    public function openAvatarPicker(): void
    {
        abort_unless($this->creator->canBeEditedBy(auth()->user()), 403);

        $this->showAvatarPicker = true;
        $this->avatarCandidates = [];
        $this->avatarPickerError = '';
        $this->avatarSearchQuery = trim($this->editName . ' ' . $this->editCountry);
    }

    public function closeAvatarPicker(): void
    {
        $this->resetAvatarPicker();
    }

    public function searchAvatarFromGoogle(CreatorAvatarService $avatar): void
    {
        abort_unless($this->creator->canBeEditedBy(auth()->user()), 403);

        $query = trim($this->avatarSearchQuery);

        if ($query === '') {
            $this->avatarPickerError = 'Enter a search query first.';
            return;
        }

        $this->avatarSearching = true;
        $this->avatarPickerError = '';

        try {
            $this->avatarCandidates = $avatar->searchCandidates($query, 12);

            if (empty($this->avatarCandidates)) {
                $this->avatarPickerError = 'No results from Google Images. Try a different query.';
            }
        } finally {
            $this->avatarSearching = false;
        }
    }

    /**
     * Download the picked image to S3 and save it as the creator's avatar
     * immediately. Matches the admin flow: profile image isn't a form field,
     * so it persists outside save().
     */
    public function pickAvatarCandidate(int $index, CreatorAvatarService $avatar): void
    {
        abort_unless($this->creator->canBeEditedBy(auth()->user()), 403);

        if (! isset($this->avatarCandidates[$index])) {
            return;
        }

        $candidate = $this->avatarCandidates[$index];

        try {
            $avatar->attachPickedUrl($this->creator, $candidate['url']);
        } catch (\Throwable $e) {
            $this->avatarPickerError = 'Failed to download: ' . $e->getMessage();
            \Log::error('CreatorQuickEdit: avatar picker download failed', [
                'creator_id' => $this->creator->id,
                'url' => $candidate['url'],
                'error' => $e->getMessage(),
            ]);
            return;
        }

        $this->creator->update([
            'profile_image_attribution' => 'Google Images (' . parse_url($candidate['url'], PHP_URL_HOST) . ')',
            'profile_image_license' => null,
        ]);

        // Drop any pending local upload — the picked image wins.
        $this->photoData = null;
        $this->photoName = null;

        $this->creator->refresh();
        $this->resetAvatarPicker();
        $this->dispatch('creator-updated');
    }

    protected function resetAvatarPicker(): void
    {
        $this->showAvatarPicker = false;
        $this->avatarSearchQuery = '';
        $this->avatarCandidates = [];
        $this->avatarSearching = false;
        $this->avatarPickerError = '';
    }

    public function addSocialLink(): void
    {
        $this->editSocialLinks[] = [
            'id' => null,
            'platform' => 'instagram',
            'url' => '',
            'handle' => '',
            'follower_count' => null,
        ];
    }

    public function removeSocialLink(int $index): void
    {
        unset($this->editSocialLinks[$index]);
        $this->editSocialLinks = array_values($this->editSocialLinks);
    }

    public function save(): void
    {
        abort_unless($this->creator->canBeEditedBy(auth()->user()), 403);

        $this->validate([
            'editName' => 'required|string|max:255',
            'editBio' => 'required|string|max:2000',
            'editCategory' => 'required|string|in:' . implode(',', $this->categories),
            'editCountry' => 'required|string|in:' . implode(',', $this->africanCountries),
            'editSocialLinks' => 'nullable|array|max:6',
            'editSocialLinks.*.platform' => 'required|string',
            'editSocialLinks.*.url' => 'nullable|url',
            'editSocialLinks.*.follower_count' => 'nullable|integer|min:0',
        ]);

        $this->creator->update([
            'name' => $this->editName,
            'bio' => $this->editBio,
            'category' => $this->editCategory,
            'country' => $this->editCountry,
        ]);

        // Handle photo upload via base64 (Spatie MediaLibrary)
        if ($this->photoData) {
            $this->savePhotoFromBase64();
        }

        // Sync social links
        $existingIds = [];
        foreach ($this->editSocialLinks as $linkData) {
            if (empty($linkData['url'])) {
                continue;
            }

            $followerCount = isset($linkData['follower_count']) && $linkData['follower_count'] !== ''
                ? (int) $linkData['follower_count']
                : null;

            if (! empty($linkData['id'])) {
                $this->creator->socialLinks()->where('id', $linkData['id'])->update([
                    'platform' => $linkData['platform'],
                    'url' => $linkData['url'],
                    'handle' => $linkData['handle'] ?: null,
                    'follower_count' => $followerCount,
                ]);
                $existingIds[] = $linkData['id'];
            } else {
                $link = $this->creator->socialLinks()->create([
                    'platform' => $linkData['platform'],
                    'url' => $linkData['url'],
                    'handle' => $linkData['handle'] ?: null,
                    'follower_count' => $followerCount,
                ]);
                $existingIds[] = $link->id;
            }
        }

        $this->creator->socialLinks()->whereNotIn('id', $existingIds)->delete();

        // Refresh the creator model with social links
        $this->creator->refresh();
        $this->creator->load('socialLinks');

        $this->editing = false;
        $this->photoData = null;
        $this->photoName = null;

        $this->dispatch('creator-updated');
    }

    protected function savePhotoFromBase64(): void
    {
        $data = $this->photoData;

        if (preg_match('/^data:(image\/[a-zA-Z+.-]+);base64,(.+)$/', $data, $matches)) {
            $mime = $matches[1];
            $payload = $matches[2];
        } else {
            $mime = 'image/jpeg';
            $payload = $data;
        }

        $binary = base64_decode($payload, true);
        if ($binary === false) {
            throw new \RuntimeException('Invalid base64 image data.');
        }

        if (strlen($binary) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Image is larger than 5MB.');
        }

        $extension = match ($mime) {
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            default => 'jpg',
        };

        $tmpPath = tempnam(sys_get_temp_dir(), 'creator_photo_') . '.' . $extension;
        file_put_contents($tmpPath, $binary);

        try {
            $this->creator->clearMediaCollection('profile_image');
            $this->creator->addMedia($tmpPath)
                ->usingFileName($this->photoName ?: ('photo.' . $extension))
                ->toMediaCollection('profile_image');
        } finally {
            if (file_exists($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    public function render()
    {
        return view('livewire.creator-quick-edit', [
            'categories' => $this->categories,
            'africanCountries' => $this->africanCountries,
        ]);
    }
}

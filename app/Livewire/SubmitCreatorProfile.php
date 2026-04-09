<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HasRecaptcha;
use App\Models\Creator;
use App\Models\CreatorSocialLink;
use Illuminate\Http\RedirectResponse;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportRedirects\Redirector;

#[Layout('components.layouts.blog')]
class SubmitCreatorProfile extends Component
{
    use HasRecaptcha;

    public string $name = '';
    public string $bio = '';
    public string $country = '';
    public string $category = '';
    public string $contactEmail = '';
    public array $socialLinks = [];
    public ?string $photoData = null;
    public ?string $photoName = null;

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

    public function mount(): void
    {
        if (auth()->user()->claimedCreators()->exists()) {
            session()->flash('info', 'You already have a creator profile.');
            $this->redirect(route('dashboard'));

            return;
        }

        $this->name = auth()->user()->name;
        $this->contactEmail = auth()->user()->email;
    }

    public function addSocialLink(): void
    {
        $this->socialLinks[] = [
            'platform' => 'instagram',
            'url' => '',
            'handle' => '',
        ];
    }

    public function removeSocialLink(int $index): void
    {
        unset($this->socialLinks[$index]);
        $this->socialLinks = array_values($this->socialLinks);
    }

    public function submit(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', function ($attribute, $value, $fail) {
                $slug = \Illuminate\Support\Str::slug($value);
                if ($slug && Creator::where('slug', $slug)->exists()) {
                    $fail('A creator profile with this name already exists. Please use a different name.');
                }
            }],
            'bio' => 'required|string|max:1000',
            'country' => 'required|string|in:' . implode(',', $this->africanCountries),
            'category' => 'required|string|in:' . implode(',', $this->categories),
            'contactEmail' => 'nullable|email|max:255',
            'socialLinks' => 'array|max:6',
            'socialLinks.*.platform' => 'required|string|in:instagram,tiktok,youtube,twitter,facebook,website',
            'socialLinks.*.url' => 'required|url|max:255',
            'socialLinks.*.handle' => 'nullable|string|max:100',
        ]);

        if (! $this->validateRecaptcha('submit_creator_profile')) {
            return;
        }

        // Re-check duplicate guard
        if (auth()->user()->claimedCreators()->exists()) {
            session()->flash('info', 'You already have a creator profile.');
            $this->redirect(route('dashboard'));

            return;
        }

        $creator = Creator::create([
            'user_id' => auth()->id(),
            'name' => $this->name,
            'bio' => $this->bio,
            'country' => $this->country,
            'category' => $this->category,
            'contact_email' => $this->contactEmail ?: null,
            'status' => 'pending',
        ]);

        if ($this->photoData) {
            $this->savePhotoFromBase64($creator);
        }

        foreach ($this->socialLinks as $link) {
            if (empty($link['url'])) {
                continue;
            }

            CreatorSocialLink::create([
                'creator_id' => $creator->id,
                'platform' => $link['platform'],
                'url' => $link['url'],
                'handle' => $link['handle'] ?: null,
            ]);
        }

        session()->flash('success', 'Your creator profile has been submitted for review. Our team will review it shortly.');
        $this->redirect(route('dashboard'));
    }

    protected function savePhotoFromBase64(Creator $creator): void
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
            'image/svg+xml' => 'svg',
            default => 'jpg',
        };

        $tmpPath = tempnam(sys_get_temp_dir(), 'creator_photo_') . '.' . $extension;
        file_put_contents($tmpPath, $binary);

        try {
            $creator->clearMediaCollection('profile_image');
            $creator->addMedia($tmpPath)
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
        return view('livewire.submit-creator-profile', [
            'categories' => $this->categories,
            'africanCountries' => $this->africanCountries,
        ]);
    }
}

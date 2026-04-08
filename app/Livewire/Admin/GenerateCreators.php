<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Creator;
use App\Models\CreatorSocialLink;
use App\Services\ClaudeBioService;
use App\Services\PerplexityService;
use App\Services\WikimediaService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class GenerateCreators extends Component
{
    public string $niche = '';
    public string $country = '';
    public bool $generating = false;
    public int $generatedCount = 0;
    public string $statusMessage = '';

    protected array $categories = [
        'Comedy', 'Fashion', 'Food', 'Music', 'Lifestyle',
        'Travel', 'Tech', 'Beauty', 'Sports', 'Education',
        'Finance', 'Health', 'Art', 'Gaming', 'Photography',
    ];

    public function rules(): array
    {
        return [
            'niche' => 'required|string',
            'country' => 'required|string|min:2',
        ];
    }

    public function generate(
        PerplexityService $perplexity,
        ClaudeBioService $claude,
        WikimediaService $wikimedia,
    ): void {
        $this->validate();

        $this->generating = true;
        $this->generatedCount = 0;
        $this->statusMessage = 'Discovering creators...';

        $creators = $perplexity->discoverCreators($this->niche, $this->country);

        if (empty($creators)) {
            $this->statusMessage = 'No creators found. Try a different niche or country.';
            $this->generating = false;

            return;
        }

        $this->statusMessage = 'Generating bios and finding images...';

        foreach ($creators as $creatorData) {
            $name = $creatorData['name'] ?? null;

            if (! $name || Creator::where('name', $name)->exists()) {
                continue;
            }

            $bio = $claude->generateBio($creatorData);
            $image = $wikimedia->searchCreatorImage($name);

            $creator = Creator::create([
                'name' => $name,
                'bio' => $bio,
                'country' => $creatorData['country'] ?? $this->country,
                'category' => $creatorData['category'] ?? $this->niche,
                'contact_email' => \App\Jobs\DiscoverCreatorsJob::normalizeContactEmail($creatorData['contact_email'] ?? null),
                'status' => 'pending',
                'profile_image_url' => $image['image_url'] ?? null,
                'profile_image_attribution' => $image['attribution'] ?? null,
                'profile_image_license' => $image['license'] ?? null,
            ]);

            $this->createSocialLinks($creator, $creatorData);
            $this->generatedCount++;
        }

        $this->statusMessage = "{$this->generatedCount} creators added to the pending queue.";
        $this->generating = false;
    }

    private function createSocialLinks(Creator $creator, array $data): void
    {
        $links = [
            ['platform' => 'instagram', 'handle' => $data['instagram_handle'] ?? null, 'url' => null],
            ['platform' => 'tiktok', 'handle' => $data['tiktok_handle'] ?? null, 'url' => null],
            ['platform' => 'youtube', 'handle' => null, 'url' => $data['youtube_channel_url'] ?? null],
            ['platform' => 'twitter', 'handle' => $data['twitter_handle'] ?? null, 'url' => null],
            ['platform' => 'website', 'handle' => null, 'url' => $data['website_url'] ?? null],
        ];

        foreach ($links as $link) {
            $handle = $link['handle'];
            $url = $link['url'];

            if (! $handle && ! $url) {
                continue;
            }

            if (! $url && $handle) {
                $url = match ($link['platform']) {
                    'instagram' => "https://instagram.com/{$handle}",
                    'tiktok' => "https://tiktok.com/@{$handle}",
                    'twitter' => "https://x.com/{$handle}",
                    default => null,
                };
            }

            if ($url) {
                $creator->socialLinks()->create([
                    'platform' => $link['platform'],
                    'url' => $url,
                    'handle' => $handle,
                ]);
            }
        }
    }

    public function render(): View
    {
        return view('livewire.admin.generate-creators', [
            'categories' => $this->categories,
        ])->title('Generate Creators');
    }
}

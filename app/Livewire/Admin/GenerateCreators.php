<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Creator;
use App\Models\CreatorSocialLink;
use App\Services\ClaudeBioService;
use App\Services\CreatorAvatarService;
use App\Services\PerplexityService;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class GenerateCreators extends Component
{
    public string $niche = '';
    public string $country = '';
    public bool $dryRun = false;
    public bool $generating = false;
    public int $generatedCount = 0;
    public string $statusMessage = '';

    /** @var array<int, array<string, mixed>> */
    public array $previewCreators = [];

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
        CreatorAvatarService $avatar,
    ): void {
        $this->validate();

        $this->generating = true;
        $this->generatedCount = 0;
        $this->previewCreators = [];
        $this->statusMessage = $this->dryRun ? 'Discovering creators (dry run)...' : 'Discovering creators...';

        $creators = $perplexity->discoverCreators($this->niche, $this->country);

        if (empty($creators)) {
            $this->statusMessage = 'No creators found. Try a different niche or country.';
            $this->generating = false;

            return;
        }

        $this->statusMessage = $this->dryRun
            ? 'Generating bios and finding images (preview — nothing will be saved)...'
            : 'Generating bios and finding images...';

        foreach ($creators as $creatorData) {
            $name = $creatorData['name'] ?? null;

            if (! $name) {
                continue;
            }

            $alreadyExists = Creator::where('name', $name)->exists();

            if ($alreadyExists && ! $this->dryRun) {
                continue;
            }

            $bio = $claude->generateBio($creatorData);
            $contactEmail = \App\Jobs\DiscoverCreatorsJob::normalizeContactEmail($creatorData['contact_email'] ?? null);
            $country = $creatorData['country'] ?? $this->country;

            if ($this->dryRun) {
                $this->previewCreators[] = [
                    'name' => $name,
                    'bio' => $bio,
                    'country' => $country,
                    'category' => $creatorData['category'] ?? $this->niche,
                    'contact_email' => $contactEmail,
                    // Dry run skips the avatar fetch (it requires a saved
                    // Creator to attach via Spatie). The auto-fetch will run
                    // when staff uncheck Dry Run and re-submit.
                    'profile_image_url' => null,
                    'socials' => $this->buildSocialPreview($creatorData),
                    'duplicate' => $alreadyExists,
                ];
                $this->generatedCount++;

                continue;
            }

            $creator = Creator::create([
                'name' => $name,
                'bio' => $bio,
                'country' => $country,
                'category' => $creatorData['category'] ?? $this->niche,
                'contact_email' => $contactEmail,
                'status' => 'pending',
            ]);

            $imageMeta = $avatar->fetchAndAttach($creator, $country);
            if ($imageMeta !== null) {
                $creator->update([
                    'profile_image_attribution' => $imageMeta['attribution'],
                    'profile_image_license' => $imageMeta['license'],
                ]);
            }

            $this->createSocialLinks($creator, $creatorData);
            $this->generatedCount++;
        }

        $this->statusMessage = $this->dryRun
            ? "Dry run complete — {$this->generatedCount} creators would be added (nothing saved)."
            : "{$this->generatedCount} creators added to the pending queue.";
        $this->generating = false;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{platform: string, handle: ?string, url: ?string}>
     */
    private function buildSocialPreview(array $data): array
    {
        $links = [
            ['platform' => 'instagram', 'handle' => $data['instagram_handle'] ?? null, 'url' => null],
            ['platform' => 'tiktok', 'handle' => $data['tiktok_handle'] ?? null, 'url' => null],
            ['platform' => 'youtube', 'handle' => null, 'url' => $data['youtube_channel_url'] ?? null],
            ['platform' => 'twitter', 'handle' => $data['twitter_handle'] ?? null, 'url' => null],
            ['platform' => 'website', 'handle' => null, 'url' => $data['website_url'] ?? null],
        ];

        return array_values(array_filter($links, fn ($l) => $l['handle'] || $l['url']));
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

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Concerns\VerifiesRecaptcha;
use App\Models\Creator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorClaimController extends Controller
{
    use VerifiesRecaptcha;

    public function show(string $token): View
    {
        $creator = Creator::with('socialLinks')
            ->where('claim_token', $token)
            ->where('claim_token_expires_at', '>', now())
            ->firstOrFail();

        return view('creators.claim', compact('creator'));
    }

    public function update(Request $request, string $token): RedirectResponse
    {
        $creator = Creator::with('socialLinks')
            ->where('claim_token', $token)
            ->where('claim_token_expires_at', '>', now())
            ->firstOrFail();

        $request->validate([
            'bio' => 'required|string|max:1000',
            'photo' => 'nullable|image|max:5120',
            'social_links' => 'nullable|array',
            'social_links.*.platform' => 'required|string',
            'social_links.*.url' => 'nullable|url',
        ]);

        $this->verifyRecaptcha($request, 'submit_creator_claim');

        // Upload photo via Spatie MediaLibrary to S3
        if ($request->hasFile('photo')) {
            $creator->addMediaFromRequest('photo')->toMediaCollection('profile_image');
        }

        // Update bio
        $creator->update([
            'bio' => $request->input('bio'),
            'pending_claim_edit' => true,
            'claimed_by_email' => $creator->claimed_by_email,
        ]);

        // Update social links
        if ($request->has('social_links')) {
            $creator->socialLinks()->delete();

            foreach ($request->input('social_links', []) as $linkData) {
                if (empty($linkData['url'])) {
                    continue;
                }

                $creator->socialLinks()->create([
                    'platform' => $linkData['platform'],
                    'url' => $linkData['url'],
                    'handle' => $linkData['handle'] ?? null,
                ]);
            }
        }

        return redirect()->route('creators.show', $creator->slug)
            ->with('success', 'Your changes have been submitted for review. Our team will approve them shortly.');
    }
}

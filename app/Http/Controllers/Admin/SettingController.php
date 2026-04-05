<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    /**
     * The settings keys managed through the admin panel.
     */
    private const SETTING_KEYS = [
        'site_name',
        'site_description',
        'site_tagline',
        'site_logo',
        'site_favicon',
        'contact_email',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_linkedin',
        'social_youtube',
        'google_analytics_id',
        'meta_title',
        'meta_description',
        'footer_text',
        'posts_per_page',
        'allow_comments',
        'moderate_comments',
    ];

    /**
     * Display the settings form.
     */
    public function index(): View
    {
        $settings = Setting::whereIn('key', self::SETTING_KEYS)
            ->pluck('value', 'key')
            ->toArray();

        // Ensure all keys exist with defaults
        foreach (self::SETTING_KEYS as $key) {
            $settings[$key] ??= '';
        }

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Update site settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'           => ['nullable', 'string', 'max:255'],
            'site_description'    => ['nullable', 'string', 'max:500'],
            'site_tagline'        => ['nullable', 'string', 'max:255'],
            'site_logo'           => ['nullable', 'string', 'max:500'],
            'site_favicon'        => ['nullable', 'string', 'max:500'],
            'contact_email'       => ['nullable', 'email', 'max:255'],
            'social_facebook'     => ['nullable', 'url', 'max:500'],
            'social_twitter'      => ['nullable', 'url', 'max:500'],
            'social_instagram'    => ['nullable', 'url', 'max:500'],
            'social_linkedin'     => ['nullable', 'url', 'max:500'],
            'social_youtube'      => ['nullable', 'url', 'max:500'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'meta_title'          => ['nullable', 'string', 'max:255'],
            'meta_description'    => ['nullable', 'string', 'max:300'],
            'footer_text'         => ['nullable', 'string', 'max:1000'],
            'posts_per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'allow_comments'      => ['nullable', 'string', 'in:0,1'],
            'moderate_comments'   => ['nullable', 'string', 'in:0,1'],
        ]);

        foreach ($validated as $key => $value) {
            if (in_array($key, self::SETTING_KEYS, true)) {
                Setting::set($key, $value ?? '');
            }
        }

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    public function gscCallback(Request $request): RedirectResponse
    {
        $code = $request->query('code');

        if (!$code) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Google Search Console authorization failed.');
        }

        $tokens = GoogleSearchConsoleService::exchangeCode($code);

        if (!$tokens || empty($tokens['refresh_token'])) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to obtain Google Search Console tokens.');
        }

        // Store the refresh token in the .env or settings
        Setting::set('gsc_refresh_token', $tokens['refresh_token']);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Google Search Console connected successfully. Add the refresh token to your .env as GOOGLE_SEARCH_CONSOLE_REFRESH_TOKEN.');
    }
}

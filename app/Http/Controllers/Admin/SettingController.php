<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestLog;
use App\Models\Setting;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

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
        'site_address',
        'contact_email',
        'social_facebook',
        'social_twitter',
        'social_instagram',
        'social_linkedin',
        'social_youtube',
        'google_analytics_id',
        'google_tag_manager_id',
        'meta_title',
        'meta_description',
        'footer_text',
        'footer_pages',
        'excluded_ips',
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

        $pages = \App\Models\Page::orderBy('title')->get(['id', 'title', 'slug']);
        $footerPageIds = json_decode(Setting::get('footer_pages', '[]'), true) ?: [];

        $monitoring = [
            'activity_count' => Activity::count(),
            'activity_today' => Activity::whereDate('created_at', today())->count(),
            'request_count' => RequestLog::count(),
            'request_errors' => RequestLog::where('status_code', '>=', 400)->count(),
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        return view('admin.settings.index', compact('settings', 'monitoring', 'pages', 'footerPageIds'));
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
            'site_address'        => ['nullable', 'string', 'max:500'],
            'contact_email'       => ['nullable', 'email', 'max:255'],
            'social_facebook'     => ['nullable', 'url', 'max:500'],
            'social_twitter'      => ['nullable', 'url', 'max:500'],
            'social_instagram'    => ['nullable', 'url', 'max:500'],
            'social_linkedin'     => ['nullable', 'url', 'max:500'],
            'social_youtube'      => ['nullable', 'url', 'max:500'],
            'google_analytics_id' => ['nullable', 'string', 'max:50'],
            'google_tag_manager_id' => ['nullable', 'string', 'max:50'],
            'meta_title'          => ['nullable', 'string', 'max:255'],
            'meta_description'    => ['nullable', 'string', 'max:300'],
            'footer_text'         => ['nullable', 'string', 'max:1000'],
            'excluded_ips'        => ['nullable', 'string', 'max:2000'],
            'footer_pages'        => ['nullable', 'array'],
            'footer_pages.*'      => ['integer', 'exists:pages,id'],
            'posts_per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'allow_comments'      => ['nullable', 'string', 'in:0,1'],
            'moderate_comments'   => ['nullable', 'string', 'in:0,1'],
        ]);

        // Store footer_pages as JSON
        if (isset($validated['footer_pages'])) {
            Setting::set('footer_pages', json_encode($validated['footer_pages']));
            unset($validated['footer_pages']);
        } else {
            Setting::set('footer_pages', '[]');
        }

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

        try {
            $tokens = GoogleSearchConsoleService::exchangeCode($code);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GSC callback error', ['error' => $e->getMessage()]);

            return redirect()->route('admin.dashboard')
                ->with('error', 'Google Search Console connection failed: ' . $e->getMessage());
        }

        if (!$tokens || empty($tokens['refresh_token'])) {
            return redirect()->route('admin.dashboard')
                ->with('error', 'Failed to obtain refresh token. Please try again.');
        }

        // Store refresh token in database settings
        Setting::set('gsc_refresh_token', $tokens['refresh_token']);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Google Search Console connected successfully! Click the Search tab to sync your data.');
    }
}

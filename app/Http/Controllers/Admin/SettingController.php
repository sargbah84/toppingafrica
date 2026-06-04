<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RequestLog;
use App\Models\Setting;
use App\Services\GoogleSearchConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        'site_keywords',
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
        'header_pages',
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

        $allPages = \App\Models\Page::orderBy('title')->get(['id', 'title', 'slug', 'template']);
        $footerPageIds = json_decode(Setting::get('footer_pages', '[]'), true) ?: [];
        $pages = $allPages; // footer picker stays alphabetical / no ordering UI

        // ── Header navigation: CMS pages ──────────────────────────────────
        // The Header nav is now a single picker over CMS pages. Built-in
        // sections like Trending and Creators were converted into seeded
        // pages by the 2026_04_07_140000_seed_template_pages migration so
        // they appear here alongside any custom pages the admin creates.
        $savedHeaderPages = self::normaliseSavedHeaderPages(
            json_decode(Setting::get('header_pages', '[]'), true) ?: []
        );

        // Same ordering rule: enabled first (saved order), then disabled.
        $headerPages = collect();
        $seenPageIds = [];
        foreach ($savedHeaderPages as $row) {
            $page = $allPages->firstWhere('id', $row['id']);
            if (! $page) {
                continue;
            }
            $headerPages->push([
                'page' => $page,
                'enabled' => true,
                'label' => $row['label'],
                'badge' => $row['badge'],
            ]);
            $seenPageIds[] = $page->id;
        }
        foreach ($allPages as $page) {
            if (in_array($page->id, $seenPageIds, true)) {
                continue;
            }
            $headerPages->push([
                'page' => $page,
                'enabled' => false,
                'label' => null,
                'badge' => null,
            ]);
        }

        $monitoring = [
            'activity_count' => Activity::count(),
            'activity_today' => Activity::whereDate('created_at', today())->count(),
            'request_count' => RequestLog::count(),
            'request_errors' => RequestLog::where('status_code', '>=', 400)->count(),
            'pending_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count(),
        ];

        return view('admin.settings.index', compact(
            'settings', 'monitoring', 'pages', 'footerPageIds', 'headerPages',
        ));
    }

    /**
     * Update site settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'site_name'           => ['nullable', 'string', 'max:255'],
            'site_description'    => ['nullable', 'string', 'max:500'],
            'site_keywords'       => ['nullable', 'string', 'max:500'],
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
            'footer_pages'             => ['nullable', 'array'],
            'footer_pages.*'           => ['integer', 'exists:pages,id'],
            'header_pages'             => ['nullable', 'array'],
            'header_pages.*'           => ['integer', 'exists:pages,id'],
            'header_page_labels'       => ['nullable', 'array'],
            'header_page_labels.*'     => ['nullable', 'string', 'max:60'],
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

        // Store header_pages as a JSON array of {id, label, badge} objects in form order.
        $headerPageLabels = $validated['header_page_labels'] ?? [];
        $headerPageBadges = $request->input('header_page_badges', []);
        $headerPagesStructured = [];
        foreach ($validated['header_pages'] ?? [] as $id) {
            $label = trim((string) ($headerPageLabels[$id] ?? ''));
            $badge = trim((string) ($headerPageBadges[$id] ?? ''));
            $headerPagesStructured[] = [
                'id' => (int) $id,
                'label' => $label !== '' ? $label : null,
                'badge' => $badge !== '' ? $badge : null,
            ];
        }
        Setting::set('header_pages', json_encode($headerPagesStructured));
        unset($validated['header_pages'], $validated['header_page_labels']);

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

    /**
     * Coerce a saved header_pages value into the canonical
     * [['id' => int, 'label' => string|null, 'badge' => string|null], ...] shape.
     * Accepts both the new {id,label,badge} object form and the legacy plain
     * int-array form so existing data keeps working.
     *
     * @param  mixed  $raw
     * @return array<int, array{id: int, label: string|null, badge: string|null}>
     */
    public static function normaliseSavedHeaderPages($raw): array
    {
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $entry) {
            if (is_int($entry) || (is_string($entry) && ctype_digit($entry))) {
                $out[] = ['id' => (int) $entry, 'label' => null, 'badge' => null];
            } elseif (is_array($entry) && isset($entry['id'])) {
                $label = isset($entry['label']) && trim((string) $entry['label']) !== ''
                    ? (string) $entry['label']
                    : null;
                $badge = isset($entry['badge']) && trim((string) $entry['badge']) !== ''
                    ? (string) $entry['badge']
                    : null;
                $out[] = ['id' => (int) $entry['id'], 'label' => $label, 'badge' => $badge];
            }
        }

        return $out;
    }

    /**
     * Cache maintenance actions — restricted to super admins so normal staff
     * with `manage settings` permission can't accidentally flush production
     * caches. Used when a deploy didn't pick up new views/config/routes.
     */
    public function runCacheAction(Request $request): RedirectResponse
    {
        if (!$request->user()?->is_super_admin) {
            abort(403, 'Only super admins can manage caches.');
        }

        $actions = [
            'clear' => ['optimize:clear', 'All caches cleared.'],
            'optimize' => ['optimize', 'Caches rebuilt (optimize).'],
            'refresh' => ['_refresh', 'Caches cleared and rebuilt.'],
        ];

        $action = (string) $request->input('cache_action');
        if (!isset($actions[$action])) {
            return back()->with('error', 'Unknown cache action.');
        }

        [$command, $message] = $actions[$action];

        try {
            if ($command === '_refresh') {
                Artisan::call('optimize:clear');
                Artisan::call('optimize');
            } else {
                Artisan::call($command);
            }
        } catch (\Throwable $e) {
            Log::error('Admin cache action failed', [
                'action' => $action,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Cache action failed: ' . $e->getMessage());
        }

        activity()
            ->causedBy($request->user())
            ->withProperties(['action' => $action, 'command' => $command])
            ->log("Admin ran cache action: {$action}");

        return back()->with('success', $message);
    }
}

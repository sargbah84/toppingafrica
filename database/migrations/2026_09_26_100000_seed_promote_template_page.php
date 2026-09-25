<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\SettingController;
use App\Models\Page;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Turn the hardcoded /promote route into a template-backed CMS page (like
     * Trending and Creators) so its slug and SEO are editable and it can be
     * picked in Settings → Header Navigation. Idempotent.
     */
    public function up(): void
    {
        $page = Page::where('template', 'promote')->first();

        if (! $page) {
            // Adopt a plain page an admin may already have made at /promote.
            $page = Page::where('slug', 'promote')->first();

            if ($page) {
                $page->update(['template' => 'promote', 'status' => 'published']);
            } else {
                $page = Page::create([
                    'title' => 'Promote with Us',
                    'slug' => 'promote',
                    'content' => '',
                    'template' => 'promote',
                    'status' => 'published',
                    'order' => 0,
                    'meta_title' => 'Promote with Topping Africa',
                    'meta_description' => 'Get your music, video, event or business featured on Topping Africa — published on our site, shared with our Facebook, Instagram and YouTube audience, and boosted for global reach.',
                ]);
            }
        }

        $header = SettingController::normaliseSavedHeaderPages(
            json_decode(Setting::get('header_pages', '[]'), true) ?: []
        );

        if (! in_array($page->id, array_column($header, 'id'), true)) {
            $header[] = ['id' => $page->id, 'label' => 'Promote', 'badge' => null];
            Setting::set('header_pages', json_encode($header));
        }
    }

    public function down(): void {}
};

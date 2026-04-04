<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Popup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class PopupService
{
    public function getActivePopupsForPage(string $path, string $device, string $visibility = 'frontend'): Collection
    {
        $cacheKey = "popups:{$visibility}:{$device}";

        $popups = Cache::remember($cacheKey, now()->addMinutes(5), function () use ($device, $visibility) {
            return Popup::query()
                ->active()
                ->currentlyScheduled()
                ->forDevice($device)
                ->forVisibility($visibility)
                ->orderByDesc('priority')
                ->get();
        });

        return $popups->filter(fn (Popup $popup) => $popup->matchesPage($path));
    }

    public function detectDevice(string $userAgent): string
    {
        $mobileKeywords = ['Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'webOS', 'BlackBerry', 'Opera Mini', 'IEMobile'];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return 'mobile';
            }
        }

        return 'desktop';
    }
}

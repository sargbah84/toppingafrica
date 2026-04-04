<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Services\PopupService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

final class PopupRenderer extends Component
{
    public Collection $popups;

    public function __construct(PopupService $popupService, string $context = 'frontend')
    {
        $path = request()->path();
        $device = $popupService->detectDevice(request()->userAgent() ?? '');

        $this->popups = $popupService->getActivePopupsForPage($path, $device, $context);
    }

    public function shouldRender(): bool
    {
        return $this->popups->isNotEmpty();
    }

    public function render(): View
    {
        return view('components.popup-renderer');
    }
}

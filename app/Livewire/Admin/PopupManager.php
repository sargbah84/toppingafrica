<?php

declare(strict_types=1);

namespace App\Livewire\Admin;

use App\Models\Popup;
use App\Models\Post;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Component;

class PopupManager extends Component
{
    // Modal state
    public bool $showModal = false;
    public ?int $editingPopupId = null;
    public string $activeTab = 'content';

    // Core fields
    public string $name = '';
    public string $type = 'modal';
    public string $content = '';
    public bool $is_active = true;

    // Trigger settings
    public string $trigger_type = 'page_load';
    public ?string $trigger_value = null;
    public int $frequency_days = 0;

    // Display rules
    public string $display_on = 'all_pages';
    public array $show_on_pages = [];
    public string $showOnPageInput = '';
    public array $showOnPageResults = [];
    public array $exclude_pages = [];
    public string $excludePageInput = '';
    public array $excludePageResults = [];
    public string $device = 'all';
    public string $visibility = 'frontend';
    public int $priority = 10;

    // Style settings
    public string $position = 'center';
    public int $width_px = 600;
    public int $max_width_vw = 90;
    public string $animation = 'fade_in';
    public bool $show_overlay = true;
    public bool $close_on_overlay_click = true;
    public bool $show_close_button = true;
    public string $shadow = 'lg';
    public int $border_radius = 12;
    public int $border_width = 0;
    public string $border_color = '#e5e7eb';
    public int $padding = 0;
    public string $bg_color = '#ffffff';
    public string $overlay_color = 'rgba(0,0,0,0.6)';

    // Scheduling
    public ?string $starts_at = null;
    public ?string $ends_at = null;

    // Delete confirmation
    public bool $showDeleteConfirm = false;
    public ?int $deletePopupId = null;

    public array $types = [
        'modal' => 'Modal',
        'announcement_bar' => 'Announcement Bar',
        'slide_in' => 'Slide In',
        'full_screen' => 'Full Screen',
        'bottom_bar' => 'Bottom Bar',
    ];

    public array $triggerTypes = [
        'page_load' => 'Page Load',
        'timed' => 'Timed',
        'scroll' => 'Scroll',
        'exit_intent' => 'Exit Intent',
        'click' => 'Click',
    ];

    public array $positions = [
        'center' => 'Center',
        'top' => 'Top',
        'bottom' => 'Bottom',
        'left' => 'Left',
        'right' => 'Right',
        'full' => 'Full',
    ];

    public array $animations = [
        'fade_in' => 'Fade In',
        'slide_down' => 'Slide Down',
        'slide_up' => 'Slide Up',
        'slide_left' => 'Slide Left',
        'slide_right' => 'Slide Right',
        'zoom_in' => 'Zoom In',
        'none' => 'None',
    ];

    public array $shadows = [
        'none' => 'None',
        'sm' => 'Small',
        'md' => 'Medium',
        'lg' => 'Large',
        'xl' => 'Extra Large',
    ];

    public array $devices = [
        'all' => 'All Devices',
        'mobile' => 'Mobile Only',
        'desktop' => 'Desktop Only',
    ];

    public array $visibilities = [
        'frontend' => 'Frontend Only',
        'backend' => 'Backend Only',
        'both' => 'Both (Frontend & Backend)',
    ];

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'type' => 'required|in:modal,announcement_bar,slide_in,full_screen,bottom_bar',
            'content' => 'nullable|string|max:65000',
            'trigger_type' => 'required|in:click,exit_intent,scroll,timed,page_load',
            'trigger_value' => 'nullable|string|max:50',
            'frequency_days' => 'integer|min:0',
            'display_on' => 'required|in:all_pages,specific_pages',
            'device' => 'required|in:all,mobile,desktop',
            'visibility' => 'required|in:frontend,backend,both',
            'priority' => 'integer|min:0|max:100',
            'position' => 'required|in:center,top,bottom,left,right,full',
            'width_px' => 'integer|min:200|max:2000',
            'max_width_vw' => 'integer|min:10|max:100',
            'animation' => 'required|in:fade_in,slide_down,slide_up,slide_left,slide_right,zoom_in,none',
            'shadow' => 'required|in:none,sm,md,lg,xl',
            'border_radius' => 'integer|min:0|max:100',
            'border_width' => 'integer|min:0|max:20',
            'border_color' => 'string|max:20',
            'padding' => 'integer|min:0|max:200',
            'bg_color' => 'string|max:20',
            'overlay_color' => 'string|max:30',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ];
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEditModal(int $popupId): void
    {
        $popup = Popup::findOrFail($popupId);
        $this->editingPopupId = $popup->id;
        $this->name = $popup->name;
        $this->type = $popup->type;
        $this->content = $popup->content ?? '';
        $this->is_active = $popup->is_active;
        $this->trigger_type = $popup->trigger_type;
        $this->trigger_value = $popup->trigger_value;
        $this->frequency_days = $popup->frequency_days;
        $this->display_on = $popup->display_on;
        $this->show_on_pages = $popup->show_on_pages ?? [];
        $this->exclude_pages = $popup->exclude_pages ?? [];
        $this->device = $popup->device;
        $this->visibility = $popup->visibility;
        $this->priority = $popup->priority;
        $this->position = $popup->position;
        $this->width_px = $popup->width_px;
        $this->max_width_vw = $popup->max_width_vw;
        $this->animation = $popup->animation;
        $this->show_overlay = $popup->show_overlay;
        $this->close_on_overlay_click = $popup->close_on_overlay_click;
        $this->show_close_button = $popup->show_close_button;
        $this->shadow = $popup->shadow;
        $this->border_radius = $popup->border_radius;
        $this->border_width = $popup->border_width;
        $this->border_color = $popup->border_color;
        $this->padding = $popup->padding;
        $this->bg_color = $popup->bg_color;
        $this->overlay_color = $popup->overlay_color;
        $this->starts_at = $popup->starts_at?->format('Y-m-d\TH:i');
        $this->ends_at = $popup->ends_at?->format('Y-m-d\TH:i');
        $this->activeTab = 'content';
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'type' => $this->type,
            'content' => $this->content,
            'is_active' => $this->is_active,
            'trigger_type' => $this->trigger_type,
            'trigger_value' => $this->trigger_value ?: null,
            'frequency_days' => $this->frequency_days,
            'display_on' => $this->display_on,
            'show_on_pages' => ! empty($this->show_on_pages) ? $this->show_on_pages : null,
            'exclude_pages' => ! empty($this->exclude_pages) ? $this->exclude_pages : null,
            'device' => $this->device,
            'visibility' => $this->visibility,
            'priority' => $this->priority,
            'position' => $this->position,
            'width_px' => $this->width_px,
            'max_width_vw' => $this->max_width_vw,
            'animation' => $this->animation,
            'show_overlay' => $this->show_overlay,
            'close_on_overlay_click' => $this->close_on_overlay_click,
            'show_close_button' => $this->show_close_button,
            'shadow' => $this->shadow,
            'border_radius' => $this->border_radius,
            'border_width' => $this->border_width,
            'border_color' => $this->border_color,
            'padding' => $this->padding,
            'bg_color' => $this->bg_color,
            'overlay_color' => $this->overlay_color,
            'starts_at' => $this->starts_at ?: null,
            'ends_at' => $this->ends_at ?: null,
        ];

        if ($this->editingPopupId) {
            $popup = Popup::findOrFail($this->editingPopupId);
            $popup->update($data);
            $this->dispatch('notify', type: 'success', message: 'Popup updated.');
        } else {
            $data['slug'] = Str::slug($this->name) . '-' . Str::random(6);
            Popup::create($data);
            $this->dispatch('notify', type: 'success', message: 'Popup created.');
        }

        $this->closeModal();
    }

    public function updatedShowOnPageInput(string $value): void
    {
        $this->showOnPageResults = $value !== '' ? $this->searchAvailablePages($value) : [];
    }

    public function updatedExcludePageInput(string $value): void
    {
        $this->excludePageResults = $value !== '' ? $this->searchAvailablePages($value) : [];
    }

    public function addShowOnPage(?string $page = null): void
    {
        $page = trim($page ?? $this->showOnPageInput);
        if ($page !== '' && ! in_array($page, $this->show_on_pages)) {
            $this->show_on_pages[] = $page;
        }
        $this->showOnPageInput = '';
        $this->showOnPageResults = [];
    }

    public function removeShowOnPage(int $index): void
    {
        unset($this->show_on_pages[$index]);
        $this->show_on_pages = array_values($this->show_on_pages);
    }

    public function addExcludePage(?string $page = null): void
    {
        $page = trim($page ?? $this->excludePageInput);
        if ($page !== '' && ! in_array($page, $this->exclude_pages)) {
            $this->exclude_pages[] = $page;
        }
        $this->excludePageInput = '';
        $this->excludePageResults = [];
    }

    public function removeExcludePage(int $index): void
    {
        unset($this->exclude_pages[$index]);
        $this->exclude_pages = array_values($this->exclude_pages);
    }

    private function searchAvailablePages(string $query): array
    {
        $query = strtolower($query);
        $pages = [];

        // Collect named GET routes (public-facing only)
        $excludePrefixes = ['admin', 'livewire', 'sanctum', 'ignition', 'test', 'connect', 'stripe', 'reddit'];
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods())) {
                continue;
            }

            $uri = $route->uri();

            // Skip admin, API, dynamic workspace routes, etc.
            $firstSegment = explode('/', $uri)[0] ?? '';
            if (in_array($firstSegment, $excludePrefixes)) {
                continue;
            }

            // Skip routes with parameters (dynamic segments)
            if (str_contains($uri, '{')) {
                continue;
            }

            if ($uri === '/') {
                $uri = '/';
            } else {
                $uri = '/' . $uri;
            }

            $name = $route->getName() ?? $uri;

            if (str_contains(strtolower($uri), $query) || str_contains(strtolower($name), $query)) {
                $pages[$uri] = [
                    'path' => $uri,
                    'label' => $uri,
                    'type' => 'page',
                ];
            }
        }

        // Search blog posts
        if (class_exists(Post::class)) {
            $posts = Post::where('status', 'published')
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                      ->orWhere('slug', 'like', "%{$query}%");
                })
                ->limit(10)
                ->get(['title', 'slug']);

            foreach ($posts as $post) {
                $path = '/' . $post->slug;
                $pages[$path] = [
                    'path' => $path,
                    'label' => $path . ' — ' . Str::limit($post->title, 40),
                    'type' => 'post',
                ];
            }
        }

        // Build wildcard suggestions dynamically from all route path prefixes
        $prefixes = [];
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods())) {
                continue;
            }

            $uri = $route->uri();
            $firstSegment = explode('/', $uri)[0] ?? '';
            if ($firstSegment === '' || in_array($firstSegment, $excludePrefixes) || str_contains($firstSegment, '{')) {
                continue;
            }

            // Build prefixes at every depth: tools → /tools/*, tools/youtube → /tools/youtube/*
            $segments = explode('/', $uri);
            $path = '';
            foreach ($segments as $segment) {
                if (str_contains($segment, '{')) {
                    break;
                }
                $path .= '/' . $segment;
                $prefixes[$path] = true;
            }
        }

        foreach (array_keys($prefixes) as $prefix) {
            $pattern = $prefix . '/*';
            if (! isset($pages[$pattern]) && str_contains(strtolower($pattern), $query)) {
                $label = str_replace('/', ' ', trim($prefix, '/'));
                $pages[$pattern] = [
                    'path' => $pattern,
                    'label' => $pattern . ' — All ' . $label . ' pages',
                    'type' => 'wildcard',
                ];
            }
        }

        // If the user typed a custom path with wildcard, always show it as an option
        $trimmedQuery = '/' . ltrim($query, '/');
        if (str_contains($query, '*') && ! isset($pages[$trimmedQuery])) {
            array_unshift($pages, [
                'path' => $trimmedQuery,
                'label' => $trimmedQuery . ' — Custom wildcard pattern',
                'type' => 'wildcard',
            ]);
        }

        return array_values(array_slice($pages, 0, 15));
    }

    public function duplicatePopup(int $popupId): void
    {
        $popup = Popup::findOrFail($popupId);
        $this->resetForm();
        $this->name = $popup->name . ' (Copy)';
        $this->type = $popup->type;
        $this->content = $popup->content ?? '';
        $this->trigger_type = $popup->trigger_type;
        $this->trigger_value = $popup->trigger_value;
        $this->frequency_days = $popup->frequency_days;
        $this->display_on = $popup->display_on;
        $this->show_on_pages = $popup->show_on_pages ?? [];
        $this->exclude_pages = $popup->exclude_pages ?? [];
        $this->device = $popup->device;
        $this->visibility = $popup->visibility;
        $this->priority = $popup->priority;
        $this->position = $popup->position;
        $this->width_px = $popup->width_px;
        $this->max_width_vw = $popup->max_width_vw;
        $this->animation = $popup->animation;
        $this->show_overlay = $popup->show_overlay;
        $this->close_on_overlay_click = $popup->close_on_overlay_click;
        $this->show_close_button = $popup->show_close_button;
        $this->shadow = $popup->shadow;
        $this->border_radius = $popup->border_radius;
        $this->border_width = $popup->border_width;
        $this->border_color = $popup->border_color;
        $this->padding = $popup->padding;
        $this->bg_color = $popup->bg_color;
        $this->overlay_color = $popup->overlay_color;
        $this->showModal = true;
    }

    public function toggleActive(int $popupId): void
    {
        $popup = Popup::findOrFail($popupId);
        $popup->update(['is_active' => ! $popup->is_active]);
        $status = $popup->is_active ? 'activated' : 'deactivated';
        $this->dispatch('notify', type: 'success', message: "Popup {$status}.");
    }

    public function confirmDelete(int $popupId): void
    {
        $this->deletePopupId = $popupId;
        $this->showDeleteConfirm = true;
    }

    public function cancelDelete(): void
    {
        $this->showDeleteConfirm = false;
        $this->deletePopupId = null;
    }

    public function deletePopup(): void
    {
        Popup::findOrFail($this->deletePopupId)->delete();
        $this->showDeleteConfirm = false;
        $this->deletePopupId = null;
        $this->dispatch('notify', type: 'success', message: 'Popup deleted.');
    }

    public function render()
    {
        $popups = Popup::orderByDesc('priority')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.admin.popup-manager', [
            'popups' => $popups,
        ]);
    }

    private function resetForm(): void
    {
        $this->editingPopupId = null;
        $this->activeTab = 'content';
        $this->name = '';
        $this->type = 'modal';
        $this->content = '';
        $this->is_active = true;
        $this->trigger_type = 'page_load';
        $this->trigger_value = null;
        $this->frequency_days = 0;
        $this->display_on = 'all_pages';
        $this->show_on_pages = [];
        $this->showOnPageInput = '';
        $this->showOnPageResults = [];
        $this->exclude_pages = [];
        $this->excludePageInput = '';
        $this->excludePageResults = [];
        $this->device = 'all';
        $this->visibility = 'frontend';
        $this->priority = 10;
        $this->position = 'center';
        $this->width_px = 600;
        $this->max_width_vw = 90;
        $this->animation = 'fade_in';
        $this->show_overlay = true;
        $this->close_on_overlay_click = true;
        $this->show_close_button = true;
        $this->shadow = 'lg';
        $this->border_radius = 12;
        $this->border_width = 0;
        $this->border_color = '#e5e7eb';
        $this->padding = 0;
        $this->bg_color = '#ffffff';
        $this->overlay_color = 'rgba(0,0,0,0.6)';
        $this->starts_at = null;
        $this->ends_at = null;
        $this->resetValidation();
    }
}

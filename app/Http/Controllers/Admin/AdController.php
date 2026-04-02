<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdController extends Controller
{
    public function index(Request $request): View
    {
        $query = Ad::query();

        if ($position = $request->input('position')) {
            $query->where('position', $position);
        }

        $ads = $query->orderBy('position')->orderBy('order')->paginate(20)->withQueryString();

        return view('admin.ads.index', compact('ads'));
    }

    public function create(): View
    {
        return view('admin.ads.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'in:header,sidebar,in_article,after_content,footer'],
            'code' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'string', 'max:500'],
            'url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        Ad::create([
            'name' => $validated['name'],
            'position' => $validated['position'],
            'code' => $validated['code'] ?? null,
            'image' => $validated['image'] ?? null,
            'url' => $validated['url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'order' => $validated['order'] ?? 0,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return redirect()->route('admin.ads.index')
            ->with('success', 'Ad created successfully.');
    }

    public function edit(Ad $ad): View
    {
        return view('admin.ads.edit', compact('ad'));
    }

    public function update(Request $request, Ad $ad): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'in:header,sidebar,in_article,after_content,footer'],
            'code' => ['nullable', 'string', 'max:10000'],
            'image' => ['nullable', 'string', 'max:500'],
            'url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['boolean'],
            'order' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        $ad->update([
            'name' => $validated['name'],
            'position' => $validated['position'],
            'code' => $validated['code'] ?? null,
            'image' => $validated['image'] ?? null,
            'url' => $validated['url'] ?? null,
            'is_active' => $request->boolean('is_active'),
            'order' => $validated['order'] ?? 0,
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
        ]);

        return redirect()->route('admin.ads.index')
            ->with('success', 'Ad updated successfully.');
    }

    public function destroy(Ad $ad): RedirectResponse
    {
        $ad->delete();

        return redirect()->route('admin.ads.index')
            ->with('success', 'Ad deleted successfully.');
    }
}

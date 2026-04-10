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

    public function edit(Ad $ad): View
    {
        return view('admin.ads.edit', compact('ad'));
    }

    public function duplicate(Ad $ad): RedirectResponse
    {
        $clone = $ad->replicate();
        $clone->name = $ad->name.' (Copy)';
        $clone->is_active = false;
        $clone->save();

        return redirect()->route('admin.ads.edit', $clone)
            ->with('success', 'Ad duplicated successfully.');
    }

    public function destroy(Ad $ad): RedirectResponse
    {
        $ad->delete();

        return redirect()->route('admin.ads.index')
            ->with('success', 'Ad deleted successfully.');
    }
}

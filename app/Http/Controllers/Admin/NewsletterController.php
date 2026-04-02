<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class NewsletterController extends Controller
{
    public function index(Request $request): View
    {
        $query = NewsletterSubscriber::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search): void {
                $q->where('email', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $subscribers = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => NewsletterSubscriber::count(),
            'subscribed' => NewsletterSubscriber::where('status', 'subscribed')->count(),
            'unsubscribed' => NewsletterSubscriber::where('status', 'unsubscribed')->count(),
        ];

        return view('admin.newsletters.index', compact('subscribers', 'stats'));
    }

    public function export(): StreamedResponse
    {
        $fileName = 'newsletter-subscribers-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Email', 'Name', 'Status', 'Subscribed At']);

            NewsletterSubscriber::where('status', 'subscribed')
                ->orderBy('subscribed_at', 'desc')
                ->chunk(500, function ($subscribers) use ($handle): void {
                    foreach ($subscribers as $subscriber) {
                        fputcsv($handle, [
                            $subscriber->email,
                            $subscriber->name ?? '',
                            $subscriber->status,
                            $subscriber->subscribed_at?->format('Y-m-d H:i:s') ?? '',
                        ]);
                    }
                });

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function destroy(NewsletterSubscriber $newsletter): \Illuminate\Http\RedirectResponse
    {
        $newsletter->delete();

        return redirect()->route('admin.newsletters.index')
            ->with('success', 'Subscriber deleted successfully.');
    }
}

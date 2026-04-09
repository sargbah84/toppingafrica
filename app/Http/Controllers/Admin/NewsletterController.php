<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

    public function show(NewsletterSubscriber $newsletter): \Illuminate\Http\JsonResponse
    {
        $location = null;

        if ($newsletter->ip_address && ! in_array($newsletter->ip_address, ['127.0.0.1', '::1'])) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$newsletter->ip_address}", [
                    'fields' => 'status,country,regionName,city,isp,query',
                ]);

                if ($response->ok() && $response->json('status') === 'success') {
                    $location = $response->json();
                }
            } catch (\Exception $e) {
                // Silently fail — location is optional
            }
        }

        return response()->json([
            'id' => $newsletter->id,
            'email' => $newsletter->email,
            'name' => $newsletter->name,
            'status' => $newsletter->status,
            'ip_address' => $newsletter->ip_address,
            'user_agent' => $newsletter->user_agent,
            'location' => $location,
            'subscribed_at' => $newsletter->subscribed_at?->format('M d, Y \a\t h:i A'),
            'unsubscribed_at' => $newsletter->unsubscribed_at?->format('M d, Y \a\t h:i A'),
            'created_at' => $newsletter->created_at?->format('M d, Y \a\t h:i A'),
        ]);
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

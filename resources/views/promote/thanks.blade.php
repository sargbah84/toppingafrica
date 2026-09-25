<x-layouts.blog title="Thank you" :noindex="true">

<section class="max-w-container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 md:p-8 text-center">
        @if(in_array($promotion->status, ['inquiry', 'invoiced']))
            <div class="w-14 h-14 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center mb-4">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Thank you, {{ $promotion->name }}!</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                We've received your request for <strong>{{ $promotion->package_name }}</strong> ({{ $promotion->formatted_amount }}).
                A confirmation is on its way to <strong>{{ $promotion->email }}</strong>.
            </p>
            <div class="text-left rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-4 text-sm text-amber-900 dark:text-amber-200 mb-6">
                <strong>Look out for our next email.</strong> Within 2 business days we'll send your invoice with a secure payment link.
                There's nothing to pay until then. Check your spam folder if you don't see it.
            </div>
        @elseif($promotion->isPaid())
            <div class="w-14 h-14 mx-auto rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 flex items-center justify-center mb-4">
                <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
            </div>
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Thank you, {{ $promotion->name }}!</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Your payment of <strong>{{ $promotion->formatted_amount }}</strong> for <strong>{{ $promotion->package_name }}</strong> is confirmed.
                A receipt is on its way to {{ $promotion->email }}.
            </p>
        @else
            <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Payment processing…</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">
                We're waiting for confirmation from your bank. You'll get an email at {{ $promotion->email }} as soon as it clears — no need to pay again.
            </p>
        @endif

        <div class="text-left rounded-md bg-gray-50 dark:bg-gray-900 p-4 text-sm text-gray-700 dark:text-gray-300 mb-6">
            <p class="font-semibold text-gray-900 dark:text-white mb-2">What happens next</p>
            <ol class="list-decimal list-inside space-y-1">
                <li>Our editors review your submission (within 2 business days).</li>
                @if(! $promotion->isPaid())
                    <li>We email your invoice — production starts once it's paid.</li>
                @endif
                <li>We may email you for extra photos, quotes or clips.</li>
                <li>We publish and share, then email you every link.</li>
            </ol>
            <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">Order reference: <strong>{{ $promotion->reference }}</strong></p>
        </div>

        <a href="{{ route('home') }}" class="inline-flex items-center px-6 py-3 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-semibold rounded-md hover:opacity-90 transition">
            Back to Topping Africa
        </a>
    </div>
</section>

</x-layouts.blog>

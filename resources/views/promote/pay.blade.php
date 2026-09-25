<x-layouts.blog title="Complete your payment" :noindex="true">

<section class="max-w-container mx-auto px-4 py-12">
    <div class="max-w-xl mx-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-6 md:p-8">
        <h1 class="text-2xl font-black text-gray-900 dark:text-white mb-2">Your order is saved</h1>
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
            Payment for order <strong>{{ $promotion->reference }}</strong> wasn't completed. Your details are saved — you can pay whenever you're ready.
        </p>

        @if($errors->has('payment'))
            <div class="mb-5 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-300">
                {{ $errors->first('payment') }}
            </div>
        @endif

        <dl class="text-sm divide-y divide-gray-100 dark:divide-gray-700 border-y border-gray-100 dark:border-gray-700 mb-6">
            <div class="flex justify-between py-2"><dt class="text-gray-500 dark:text-gray-400">Package</dt><dd class="font-medium text-gray-900 dark:text-white">{{ $promotion->package_name }}</dd></div>
            @if($promotion->addon_names)
                <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500 dark:text-gray-400">Add-ons</dt><dd class="font-medium text-gray-900 dark:text-white text-right">{{ implode(', ', $promotion->addon_names) }}</dd></div>
            @endif
            <div class="flex justify-between gap-4 py-2"><dt class="text-gray-500 dark:text-gray-400">Promoting</dt><dd class="font-medium text-gray-900 dark:text-white text-right">{{ $promotion->subject_name }} — {{ $promotion->title }}</dd></div>
            <div class="flex justify-between py-2"><dt class="text-gray-500 dark:text-gray-400">Total</dt><dd class="font-black text-gray-900 dark:text-white">{{ $promotion->formatted_amount }}</dd></div>
        </dl>

        <a href="{{ $promotion->checkoutUrl() }}"
           class="block w-full text-center px-6 py-3 bg-primary text-white font-semibold rounded-md hover:bg-primary-hover transition-colors">
            Pay {{ $promotion->formatted_amount }} securely
        </a>
        <a href="{{ route('promote.index') }}" class="block text-center text-sm text-gray-500 dark:text-gray-400 hover:underline mt-4">
            Start a different order
        </a>
    </div>
</section>

</x-layouts.blog>

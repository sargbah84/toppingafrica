<x-layouts.blog
    title="Sponsored articles"
    :metaDescription="'Your sponsored article submissions on Topping Africa.'"
    :noindex="true"
>

<section class="max-w-container mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row gap-6">
        {{-- Left sidebar --}}
        @include('dashboard.partials.sidebar')

        {{-- Main content --}}
        <div class="flex-1 min-w-0">
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-black text-gray-900 dark:text-white">Sponsored articles</h1>
                <a href="{{ route('sponsored.create') }}"
                   class="inline-flex items-center justify-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-md hover:bg-primary-hover transition-colors">
                    Submit an article
                </a>
            </div>

            @if(session('success'))
                <div class="mb-5 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                    <p class="text-sm text-green-700 dark:text-green-300">{{ session('success') }}</p>
                </div>
            @endif

            @if($submissions->isEmpty())
                <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Have an article about your business, product or project? Submit it for publication on Topping Africa.
                        We review every article first — you only pay once it's approved.
                    </p>
                    <a href="{{ template_url('promote') }}#sponsored" class="mt-3 inline-block text-sm font-semibold text-primary hover:underline">
                        See packages and pricing →
                    </a>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($submissions as $submission)
                        <div class="bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 p-5">
                            <div class="flex items-start gap-4">
                                <div class="flex-1 min-w-0">
                                    <h3 class="text-base font-bold text-gray-900 dark:text-white">{{ $submission->title }}</h3>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $submission->reference }} &middot; {{ $submission->package_name }} &middot; {{ $submission->formatted_amount }}
                                        &middot; Submitted {{ $submission->created_at->format('M j, Y') }}
                                    </p>
                                    <p class="mt-1.5 text-xs font-medium text-gray-600 dark:text-gray-400">{{ $submission->buyer_status }}</p>
                                    @if($submission->awaitingPayment() && ! $canPayOnline)
                                        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">We've emailed your invoice with a payment link.</p>
                                    @endif
                                </div>

                                <div class="flex flex-col gap-2 flex-shrink-0">
                                    @if($submission->awaitingPayment() && $canPayOnline)
                                        <a href="{{ $submission->checkoutUrl() }}"
                                           class="inline-flex items-center justify-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-md hover:bg-primary-hover transition-colors">
                                            Pay {{ $submission->formatted_amount }}
                                        </a>
                                    @endif
                                    @if($submission->post?->status === 'published')
                                        <a href="{{ route('blog.show', $submission->post->slug) }}" target="_blank"
                                           class="inline-flex items-center justify-center px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                                            View
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>{{-- end main content --}}
    </div>{{-- end flex row --}}
</section>

</x-layouts.blog>

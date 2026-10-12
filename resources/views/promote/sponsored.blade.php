<x-layouts.blog
    title="Submit a sponsored article"
    :metaDescription="'Submit your own article for publication on Topping Africa.'"
    :noindex="true"
>

<section class="max-w-container mx-auto px-4 py-10">
    <a href="{{ template_url('promote') }}#sponsored" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-primary mb-4">
        &larr; Back to packages
    </a>
    <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white mb-2">Submit a sponsored article</h1>
    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-2xl">
        Paste your article below. There's nothing to pay today — our editors review it within 2 business days,
        and you pay only if it's approved. It is published after payment.
    </p>

    @if($errors->any())
        <div class="mb-6 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
            <p class="text-sm font-semibold text-red-700 dark:text-red-300 mb-1">Please fix the following:</p>
            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-300 space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('sponsored.store') }}" method="POST" enctype="multipart/form-data"
          x-data="sponsoredForm(@js(array_map(fn ($p) => ['price' => $p['price'], 'maxWords' => $p['max_words']], $packages)), @js(old('package', $selectedPackage)))"
          x-on:submit="if (submitting) { $event.preventDefault(); return; } submitting = true"
          class="grid gap-8 lg:grid-cols-3 lg:items-start">
        @csrf
        <input type="hidden" name="package" :value="package">

        {{-- Left: details --}}
        <div class="lg:col-span-2 space-y-8">

            <fieldset class="space-y-4">
                <legend class="text-lg font-bold text-gray-900 dark:text-white mb-3">Your details</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Full name *</label>
                        <input id="name" name="name" type="text" required maxlength="120" value="{{ old('name', auth()->user()->name) }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email</label>
                        <input id="email" type="email" value="{{ auth()->user()->email }}" disabled
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-900 text-gray-500 dark:text-gray-400">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">We'll reply to your account email.</p>
                    </div>
                    <div>
                        <label for="subject_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Company / brand name *</label>
                        <input id="subject_name" name="subject_name" type="text" required maxlength="150" value="{{ old('subject_name') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone / WhatsApp</label>
                        <input id="phone" name="phone" type="text" maxlength="40" value="{{ old('phone') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                </div>
            </fieldset>

            <fieldset class="space-y-4">
                <legend class="text-lg font-bold text-gray-900 dark:text-white mb-3">Your article</legend>
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Headline *</label>
                    <input id="title" name="title" type="text" required maxlength="200" value="{{ old('title') }}"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="article_body" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Article text *</label>
                    <textarea id="article_body" name="article_body" rows="18" required maxlength="30000" x-model="body"
                              placeholder="Paste your article as plain text. Leave a blank line between paragraphs."
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">{{ old('article_body') }}</textarea>
                    <p class="text-xs mt-1" :class="words < {{ $minWords }} || words > maxWords ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400'">
                        <span x-text="words"></span> words — between {{ number_format($minWords) }} and <span x-text="maxWords.toLocaleString('en-US')"></span> for this package.
                        Plain text only; formatting and extra links are removed.
                    </p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="primary_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Link to include *</label>
                        <input id="primary_url" name="primary_url" type="url" required maxlength="500" value="{{ old('primary_url') }}"
                               placeholder="https://yourcompany.com"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="anchor_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Link text *</label>
                        <input id="anchor_text" name="anchor_text" type="text" required maxlength="120" value="{{ old('anchor_text') }}"
                               placeholder="Words in your article the link sits on"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                </div>
                <div>
                    <label for="assets" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Images (up to 3, JPG/PNG/WebP, 8 MB each)</label>
                    <input id="assets" name="assets[]" type="file" multiple accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 dark:file:bg-gray-700 file:text-sm file:font-semibold file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-200">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="preferred_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preferred publish date</label>
                        <input id="preferred_date" name="preferred_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('preferred_date') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes for our editors</label>
                    <textarea id="description" name="description" rows="3" maxlength="2000"
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">{{ old('description') }}</textarea>
                </div>
            </fieldset>

            <fieldset class="space-y-3">
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="rights_confirmed" value="1" required @checked(old('rights_confirmed'))
                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary">
                    <span>This article is original, I own it or have permission to publish it, and I own or have permission to use the images I'm submitting.</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="terms" value="1" required @checked(old('terms'))
                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary">
                    <span>I understand the article is labelled <strong>Sponsored</strong>, its link is marked <code>rel="sponsored"</code>, and Topping Africa may edit it or decline articles that aren't relevant to Africa or its diaspora, or that promote gambling, adult content, political campaigns or unverified financial offers.</span>
                </label>
            </fieldset>
        </div>

        {{-- Right: order summary --}}
        <aside class="lg:sticky lg:top-24 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 p-5 space-y-5">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white mb-3">Package</h2>
                <div class="space-y-2">
                    @foreach($packages as $key => $package)
                        <label class="flex items-center gap-3 rounded-md border p-3 cursor-pointer transition-colors"
                               :class="package === @js($key) ? 'border-primary bg-white dark:bg-gray-800' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'">
                            <input type="radio" value="{{ $key }}" x-model="package"
                                   class="border-gray-300 dark:border-gray-600 text-primary focus:ring-primary">
                            <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $package['name'] }}</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ \App\Models\PromotionRequest::formatMoney($package['price']) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <span class="font-semibold text-gray-900 dark:text-white">Total if approved</span>
                <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="money(packages[package].price)"></span>
            </div>

            <x-turnstile action="sponsored_article" />

            <button type="submit" :disabled="submitting"
                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-primary text-white font-semibold rounded-md hover:bg-primary-hover disabled:opacity-60 transition-colors">
                <span x-show="!submitting">Submit for review</span>
                <span x-show="submitting" x-cloak>Sending…</span>
            </button>
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">No payment now. You pay only after your article is approved.</p>
        </aside>
    </form>
</section>

<script>
    function sponsoredForm(packages, initialPackage) {
        return {
            packages,
            package: initialPackage,
            body: @js(old('article_body', '')),
            submitting: false,
            get words() {
                return this.body.trim() ? this.body.trim().split(/\s+/).length : 0;
            },
            get maxWords() {
                return packages[this.package].maxWords;
            },
            money(cents) {
                return '$' + (cents / 100).toLocaleString('en-US', { minimumFractionDigits: cents % 100 ? 2 : 0 });
            },
        };
    }
</script>

</x-layouts.blog>

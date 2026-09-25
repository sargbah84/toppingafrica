<x-layouts.blog
    title="Start your promotion"
    :metaDescription="'Tell us about your release and choose a Topping Africa promotion package.'"
    :canonical="route('promote.checkout')"
>

<section class="max-w-container mx-auto px-4 py-10">
    <a href="{{ template_url('promote') }}#packages" class="inline-flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400 hover:text-primary mb-4">
        &larr; Back to packages
    </a>
    <h1 class="text-2xl md:text-3xl font-black text-gray-900 dark:text-white mb-2">Start your promotion</h1>
    <p class="text-gray-600 dark:text-gray-400 mb-8 max-w-2xl">
        {{ $onlinePayments
            ? "Tell us about what you're promoting. You'll pay securely on the next step."
            : "Tell us about what you're promoting. There's nothing to pay today — we'll review your request and email you an invoice with a secure payment link." }}
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

    <form action="{{ route('promote.store') }}" method="POST" enctype="multipart/form-data"
          x-data="promoteForm(@js($pricing), @js(old('package', $selectedPackage)), @js(old('addons', [])))"
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
                        <input id="name" name="name" type="text" required maxlength="120" value="{{ old('name', auth()->user()?->name) }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email *</label>
                        <input id="email" name="email" type="email" required maxlength="190" value="{{ old('email', auth()->user()?->email) }}"
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
                <legend class="text-lg font-bold text-gray-900 dark:text-white mb-3">What are you promoting?</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="promo_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type *</label>
                        <select id="promo_type" name="promo_type" required
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                            @foreach($promoTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('promo_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="subject_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Artist / brand name *</label>
                        <input id="subject_name" name="subject_name" type="text" required maxlength="150" value="{{ old('subject_name') }}"
                               placeholder="e.g. Danny Attah"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                </div>
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title of the release, event or product *</label>
                    <input id="title" name="title" type="text" required maxlength="200" value="{{ old('title') }}"
                           placeholder="e.g. Praise Partner (Official Music Video)"
                           class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                </div>
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tell us the story *</label>
                    <textarea id="description" name="description" rows="6" required minlength="40" maxlength="5000"
                              placeholder="What is it, who's behind it, what makes it special, release date, collaborators, quotes you'd like included…"
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">{{ old('description') }}</textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="primary_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Main link *</label>
                        <input id="primary_url" name="primary_url" type="url" required maxlength="500" value="{{ old('primary_url') }}"
                               placeholder="https://youtube.com/watch?v=…"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                    <div>
                        <label for="preferred_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Preferred publish date</label>
                        <input id="preferred_date" name="preferred_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('preferred_date') }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary">
                    </div>
                </div>

                <details class="rounded-md border border-gray-200 dark:border-gray-700 p-4" @if(old('links')) open @endif>
                    <summary class="text-sm font-semibold text-gray-900 dark:text-white cursor-pointer">Other links (optional)</summary>
                    <div class="grid gap-4 sm:grid-cols-2 mt-4">
                        @foreach($linkFields as $key => $label)
                            <div>
                                <label for="links_{{ $key }}" class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</label>
                                <input id="links_{{ $key }}" name="links[{{ $key }}]" type="url" maxlength="500" value="{{ old("links.$key") }}"
                                       class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 dark:text-white focus:border-primary focus:ring-primary text-sm">
                            </div>
                        @endforeach
                    </div>
                </details>

                <div>
                    <label for="assets" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Photos / cover art (up to 5, JPG/PNG/WebP, 8 MB each)</label>
                    <input id="assets" name="assets[]" type="file" multiple accept="image/jpeg,image/png,image/webp"
                           class="block w-full text-sm text-gray-600 dark:text-gray-400 file:mr-3 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 dark:file:bg-gray-700 file:text-sm file:font-semibold file:text-gray-700 dark:file:text-gray-200 hover:file:bg-gray-200">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">For video packages, we'll ask for clips by email.</p>
                </div>
            </fieldset>

            <fieldset class="space-y-3">
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="rights_confirmed" value="1" required @checked(old('rights_confirmed'))
                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary">
                    <span>I own, or have permission to use, the music, video, images and trademarks I'm submitting, and I allow Topping Africa to use them (including clips of the audio in video content) to promote this release.</span>
                </label>
                <label class="flex items-start gap-3 text-sm text-gray-700 dark:text-gray-300">
                    <input type="checkbox" name="terms" value="1" required @checked(old('terms'))
                           class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary">
                    <span>I understand promoted content is labelled as <strong>Sponsored</strong> and that Topping Africa keeps final editorial control and may decline requests that don't fit its guidelines.</span>
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
                            <input type="radio" value="{{ $key }}" x-model="package" @change="dropExcluded()"
                                   class="border-gray-300 dark:border-gray-600 text-primary focus:ring-primary">
                            <span class="flex-1 text-sm font-semibold text-gray-900 dark:text-white">{{ $package['name'] }}</span>
                            <span class="text-sm font-bold text-gray-900 dark:text-white">{{ \App\Models\PromotionRequest::formatMoney($package['price']) }}</span>
                        </label>
                    @endforeach
                </div>
                <a href="{{ template_url('promote') }}#packages" target="_blank" class="inline-block mt-2 text-xs text-gray-500 dark:text-gray-400 hover:text-primary">Compare packages ↗</a>
            </div>

            <div>
                <h2 class="text-sm font-bold uppercase tracking-wide text-gray-900 dark:text-white mb-3">Add-ons</h2>
                <div class="space-y-2">
                    @foreach($addons as $key => $addon)
                        <label class="flex items-start gap-3 text-sm cursor-pointer" x-show="available(@js($key))">
                            <input type="checkbox" name="addons[]" value="{{ $key }}" x-model="addons"
                                   class="mt-0.5 rounded border-gray-300 dark:border-gray-600 text-primary focus:ring-primary">
                            <span class="flex-1 text-gray-700 dark:text-gray-300" title="{{ $addon['description'] }}">{{ $addon['name'] }}</span>
                            <span class="text-gray-500 dark:text-gray-400">+{{ \App\Models\PromotionRequest::formatMoney($addon['price']) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="pt-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-between">
                <span class="font-semibold text-gray-900 dark:text-white">Total</span>
                <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="money(total)"></span>
            </div>

            <x-turnstile action="promotion_request" />

            <button type="submit" :disabled="submitting"
                    class="w-full inline-flex items-center justify-center px-6 py-3 bg-primary text-white font-semibold rounded-md hover:bg-primary-hover disabled:opacity-60 transition-colors">
                <span x-show="!submitting">{{ $onlinePayments ? 'Continue to payment' : 'Submit request' }}</span>
                <span x-show="submitting" x-cloak>Sending…</span>
            </button>
            <p class="text-xs text-gray-500 dark:text-gray-400 text-center">
                {{ $onlinePayments
                    ? 'Payments are processed securely by Stripe.'
                    : 'No payment now. We\'ll email your invoice within 2 business days.' }}
            </p>
        </aside>
    </form>
</section>

<script>
    function promoteForm(pricing, initialPackage, initialAddons) {
        return {
            package: initialPackage,
            addons: initialAddons,
            submitting: false,
            available(addon) {
                return !pricing.addons[addon].excluded.includes(this.package);
            },
            dropExcluded() {
                this.addons = this.addons.filter(a => this.available(a));
            },
            get total() {
                return pricing.packages[this.package] + this.addons
                    .filter(a => this.available(a))
                    .reduce((sum, a) => sum + pricing.addons[a].price, 0);
            },
            money(cents) {
                return '$' + (cents / 100).toLocaleString('en-US', { minimumFractionDigits: cents % 100 ? 2 : 0 });
            },
        };
    }
</script>

</x-layouts.blog>

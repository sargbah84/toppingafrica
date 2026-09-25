<div>
    <x-slot name="header">Promotions</x-slot>

    @if(session('success'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-3 text-sm text-green-700 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3 text-sm text-red-700 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            Paid orders from <a href="{{ template_url('promote') }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">the Promote page</a>.
            Revenue to date: <strong class="text-gray-900 dark:text-white">{{ \App\Models\PromotionRequest::formatMoney((int) $revenue) }}</strong>
        </p>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search orders…"
               class="rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm w-64">
    </div>

    {{-- Filter Tabs --}}
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
        <nav class="-mb-px flex gap-x-6 overflow-x-auto" aria-label="Tabs">
            @foreach([
                'inquiries' => 'New inquiries',
                'awaiting_payment' => 'Awaiting payment',
                'open' => 'Paid — to do',
                'live' => 'Live',
                'completed' => 'Completed',
                'closed' => 'Declined / cancelled',
                'all' => 'All',
            ] as $key => $label)
                <button wire:click="$set('filter', '{{ $key }}')"
                        class="whitespace-nowrap border-b-2 pb-3 px-1 text-sm font-medium transition {{ $filter === $key ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-gray-500 dark:text-gray-400 hover:border-gray-300 hover:text-gray-700 dark:hover:text-gray-300' }}">
                    {{ $label }}
                    <span class="ml-1 rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 text-xs font-medium text-gray-600 dark:text-gray-300">{{ $counts[$key] }}</span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Orders --}}
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            <thead class="bg-gray-50 dark:bg-gray-900/50">
                <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-3">Order</th>
                    <th class="px-4 py-3">Promoting</th>
                    <th class="px-4 py-3">Package</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($promotions as $promotion)
                    <tr wire:key="promo-{{ $promotion->id }}" class="hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <td class="px-4 py-3">
                            <div class="font-mono font-semibold text-gray-900 dark:text-white">{{ $promotion->reference }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $promotion->created_at->format('M j, Y') }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $promotion->subject_name }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400 truncate max-w-xs">{{ $promotion->title }}</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            {{ $promotion->package_name }}
                            @if($promotion->addons)
                                <span class="text-xs text-gray-500 dark:text-gray-400">+{{ count($promotion->addons) }} add-on{{ count($promotion->addons) > 1 ? 's' : '' }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $promotion->formatted_amount }}</td>
                        <td class="px-4 py-3">
                            <span @class([
                                'inline-flex rounded-full px-2 py-0.5 text-xs font-semibold',
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' => in_array($promotion->status, ['inquiry', 'paid']),
                                'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300' => $promotion->status === 'invoiced',
                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' => $promotion->status === 'in_production',
                                'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' => in_array($promotion->status, ['live', 'completed']),
                                'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' => in_array($promotion->status, ['pending_payment', 'declined', 'cancelled']),
                            ])>{{ $promotion->status_label }}</span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="open('{{ $promotion->reference }}')" class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium">Open</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">No orders here yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $promotions->links() }}</div>

    {{-- Detail slide-over --}}
    @if($selected)
        <div class="fixed inset-0 z-50 flex justify-end" wire:key="detail-{{ $selected->reference }}">
            <div class="absolute inset-0 bg-black/40" wire:click="close"></div>
            <div class="relative w-full max-w-2xl h-full overflow-y-auto bg-white dark:bg-gray-800 shadow-xl">
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $selected->subject_name }} — {{ $selected->title }}</h2>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            <span class="font-mono">{{ $selected->reference }}</span> · {{ $selected->package_name }} · {{ $selected->formatted_amount }}
                            @if($selected->paid_at) · paid {{ $selected->paid_at->diffForHumans() }} @endif
                        </p>
                    </div>
                    <button wire:click="close" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Close">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 space-y-6 text-sm">
                    {{-- Order + contact --}}
                    <dl class="grid grid-cols-3 gap-x-4 gap-y-2">
                        <dt class="text-gray-500 dark:text-gray-400">Contact</dt>
                        <dd class="col-span-2 text-gray-900 dark:text-white">
                            {{ $selected->name }} · <a href="mailto:{{ $selected->email }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">{{ $selected->email }}</a>
                            @if($selected->phone) · {{ $selected->phone }} @endif
                        </dd>
                        <dt class="text-gray-500 dark:text-gray-400">Type</dt>
                        <dd class="col-span-2 text-gray-900 dark:text-white">{{ config("promotions.promo_types.{$selected->promo_type}", $selected->promo_type) }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Add-ons</dt>
                        <dd class="col-span-2 text-gray-900 dark:text-white">{{ implode(', ', $selected->addon_names) ?: '—' }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Preferred date</dt>
                        <dd class="col-span-2 text-gray-900 dark:text-white">{{ $selected->preferred_date?->format('M j, Y') ?? '—' }}</dd>
                        <dt class="text-gray-500 dark:text-gray-400">Links</dt>
                        <dd class="col-span-2 space-y-1">
                            <a href="{{ $selected->primary_url }}" target="_blank" rel="noopener" class="block text-indigo-600 dark:text-indigo-400 hover:underline break-all">{{ $selected->primary_url }}</a>
                            @foreach($selected->links ?? [] as $label => $url)
                                <a href="{{ $url }}" target="_blank" rel="noopener" class="block text-indigo-600 dark:text-indigo-400 hover:underline break-all">
                                    <span class="text-gray-500 dark:text-gray-400">{{ \App\Http\Controllers\PromotionController::LINK_FIELDS[$label] ?? $label }}:</span> {{ $url }}
                                </a>
                            @endforeach
                        </dd>
                    </dl>

                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Their story</h3>
                        <p class="whitespace-pre-line text-gray-700 dark:text-gray-300 rounded-md bg-gray-50 dark:bg-gray-900/50 p-3">{{ $selected->description }}</p>
                    </div>

                    @if($selected->getMedia('assets')->isNotEmpty())
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Uploaded images</h3>
                            <div class="grid grid-cols-3 gap-2">
                                @foreach($selected->getMedia('assets') as $media)
                                    <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="block aspect-square overflow-hidden rounded-md bg-gray-100 dark:bg-gray-700">
                                        <img src="{{ $media->getUrl() }}" alt="{{ $media->file_name }}" class="w-full h-full object-cover">
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <hr class="border-gray-200 dark:border-gray-700">

                    {{-- Workflow --}}
                    <form wire:submit="save" class="space-y-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                                <select wire:model="status" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach(\App\Models\PromotionRequest::STATUSES as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Inquiry → Invoice sent → Paid → In production → Live. Setting <strong>Paid</strong> records the payment date; <strong>Live</strong> emails the buyer their links (once).</p>
                            </div>
                            <div>
                                <label class="block font-medium text-gray-700 dark:text-gray-300 mb-1">Article (post ID, slug or URL)</label>
                                <input wire:model="postLookup" type="text" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('postLookup') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                @if($selected->post)
                                    <p class="text-xs mt-1">
                                        <a href="{{ route('admin.blog.posts.edit', $selected->post->id) }}" class="text-indigo-600 dark:text-indigo-400 hover:underline">Edit “{{ \Illuminate\Support\Str::limit($selected->post->title, 40) }}”</a>
                                        <span class="text-gray-500">({{ $selected->post->status }})</span>
                                    </p>
                                @else
                                    <button type="button" wire:click="createDraftPost" class="text-xs mt-1 text-indigo-600 dark:text-indigo-400 hover:underline">
                                        + Create sponsored draft from this order
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Published on</h3>
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach(\App\Livewire\Admin\ManagePromotions::DELIVERABLES as $key => $label)
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }} URL</label>
                                        <input wire:model="deliverables.{{ $key }}" type="url" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        @error("deliverables.$key") <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">Results</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                Article views: <strong>{{ $selected->post ? number_format($selected->post->views()->count()) : '—' }}</strong> (automatic). Enter social numbers from each platform's insights.
                            </p>
                            <div class="grid gap-3 sm:grid-cols-3">
                                @foreach(\App\Livewire\Admin\ManagePromotions::METRICS as $key => $label)
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">{{ $label }}</label>
                                        <input wire:model="metrics.{{ $key }}" type="number" min="0" step="any" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="block font-semibold text-gray-900 dark:text-white mb-1">Internal notes</label>
                            <textarea wire:model="adminNotes" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            @if($selected->stripe_payment_intent)
                                <a href="https://dashboard.stripe.com/payments/{{ $selected->stripe_payment_intent }}" target="_blank" rel="noopener" class="text-xs text-gray-500 dark:text-gray-400 hover:underline">
                                    View / refund in Stripe ↗
                                </a>
                            @else
                                <span></span>
                            @endif
                            <button type="submit" wire:loading.attr="disabled" class="px-5 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 disabled:opacity-60">
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
</div>

<div>
    {{-- Popups Table --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
        <div class="p-4 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <p class="text-sm text-gray-600 dark:text-gray-400">Create and manage popups displayed across the site.</p>
            </div>
            <button wire:click="openCreateModal" class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="-ml-0.5 mr-1.5 h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                New Popup
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-y border-gray-200">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Trigger</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Display</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($popups as $popup)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="font-medium text-gray-900">{{ $popup->name }}</p>
                            <p class="text-xs text-gray-500">{{ Str::limit(strip_tags($popup->content), 50) }}</p>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700">
                                {{ $types[$popup->type] ?? $popup->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $triggerTypes[$popup->trigger_type] ?? $popup->trigger_type }}
                            @if($popup->trigger_value)
                                <span class="text-gray-400">({{ $popup->trigger_value }})</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            <div class="space-y-0.5">
                                <p class="text-xs">{{ $popup->display_on === 'all_pages' ? 'All Pages' : 'Specific Pages' }}</p>
                                <p class="text-xs text-gray-400">{{ $devices[$popup->device] ?? $popup->device }}</p>
                                <p class="text-xs">
                                    @if($popup->visibility === 'both')
                                        <span class="text-indigo-600 font-medium">Frontend & Backend</span>
                                    @elseif($popup->visibility === 'backend')
                                        <span class="text-amber-600 font-medium">Backend Only</span>
                                    @else
                                        <span class="text-gray-400">Frontend Only</span>
                                    @endif
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $popup->priority }}
                        </td>
                        <td class="px-6 py-4">
                            @php $status = $popup->getScheduleStatus(); @endphp
                            @if($status === 'Active')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Active</span>
                            @elseif($status === 'Scheduled')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-700">Scheduled</span>
                            @elseif($status === 'Expired')
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-amber-100 text-amber-700">Expired</span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-500">Inactive</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <button wire:click="openEditModal({{ $popup->id }})" class="text-gray-600 hover:text-indigo-600 transition-colors" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button wire:click="duplicatePopup({{ $popup->id }})" class="text-gray-600 hover:text-blue-600 transition-colors" title="Duplicate">
                                <i class="fas fa-copy"></i>
                            </button>
                            <button wire:click="toggleActive({{ $popup->id }})" class="text-gray-600 hover:text-amber-600 transition-colors" title="{{ $popup->is_active ? 'Deactivate' : 'Activate' }}">
                                <i class="fas fa-{{ $popup->is_active ? 'pause' : 'play' }}"></i>
                            </button>
                            <button wire:click="confirmDelete({{ $popup->id }})" class="text-gray-600 hover:text-red-600 transition-colors" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-window-restore text-4xl text-gray-300 mb-3"></i>
                            <p class="text-lg font-medium">No popups yet</p>
                            <p class="text-sm mt-1">Create your first popup to display across the platform.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
    <div class="fixed inset-0 z-50" x-data>
        <div class="fixed inset-0 bg-black/50" @click="$wire.closeModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            {{-- Header --}}
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-white px-6 py-4 rounded-t-xl flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <h3 class="text-lg font-semibold">{{ $editingPopupId ? 'Edit Popup' : 'Create New Popup' }}</h3>
                        {{-- Enable/Disable Toggle --}}
                        <div class="flex items-center space-x-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input wire:model.live="is_active" type="checkbox" class="sr-only peer">
                                <div class="w-9 h-5 bg-white/30 peer-focus:ring-2 peer-focus:ring-green-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-green-500"></div>
                            </label>
                            <span class="text-sm font-medium {{ $is_active ? 'text-green-300' : 'text-white/60' }}">
                                {{ $is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                    </div>
                    <button wire:click="closeModal" class="text-white/70 hover:text-white">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>

            {{-- Tabs --}}
            <div class="border-b border-gray-200 px-6 flex-shrink-0">
                <nav class="flex space-x-6 -mb-px">
                    @foreach(['content' => 'Content', 'trigger' => 'Trigger', 'display' => 'Display Rules', 'style' => 'Style'] as $tab => $label)
                    <button wire:click="$set('activeTab', '{{ $tab }}')"
                        class="py-3 px-1 text-sm font-medium border-b-2 transition-colors {{ $activeTab === $tab ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                        @if($tab === 'content') <i class="fas fa-pen-to-square mr-1.5"></i>
                        @elseif($tab === 'trigger') <i class="fas fa-bolt mr-1.5"></i>
                        @elseif($tab === 'display') <i class="fas fa-eye mr-1.5"></i>
                        @elseif($tab === 'style') <i class="fas fa-palette mr-1.5"></i>
                        @endif
                        {{ $label }}
                    </button>
                    @endforeach
                </nav>
            </div>

            {{-- Body --}}
            <div class="p-6 space-y-6 overflow-y-auto flex-1">

                {{-- ===== CONTENT TAB ===== --}}
                @if($activeTab === 'content')
                <div class="space-y-5">
                    {{-- Name & Type --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Popup Name</label>
                            <input wire:model="name" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600" placeholder="e.g. Welcome Offer">
                            @error('name') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Popup Type</label>
                            <select wire:model="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                                @foreach($types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('type') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    {{-- Content Editor --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Content (HTML)</label>
                        <div x-data="{
                            mode: 'preview',
                            showTemplates: false,
                            popupType: $wire.entangle('type'),
                            templates: {
                                'newsletter': { name: 'Newsletter Signup', icon: 'fa-envelope', color: 'brand', types: ['modal', 'slide_in', 'full_screen'], html: `<div style=&quot;padding: 40px 32px; text-align: center;&quot;><div style=&quot;width: 64px; height: 64px; background: linear-gradient(135deg, #FF5437, #FF7A63); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px;&quot;><i class=&quot;fas fa-envelope-open-text&quot; style=&quot;color: white; font-size: 24px;&quot;></i></div><h2 style=&quot;font-size: 24px; font-weight: 700; color: #1a1d2e; margin: 0 0 8px;&quot;>Stay in the Loop</h2><p style=&quot;font-size: 15px; color: #6b7280; margin: 0 0 24px; line-height: 1.6;&quot;>Get weekly tips on growing your social media presence. Join 10,000+ creators.</p><form style=&quot;max-width: 400px; margin: 0 auto;&quot;><input type=&quot;email&quot; placeholder=&quot;Enter your email&quot; style=&quot;width: 100%; padding: 12px 16px; border: 1px solid #e5e7eb; border-radius: 10px; font-size: 14px; margin-bottom: 12px; box-sizing: border-box;&quot;><button type=&quot;submit&quot; style=&quot;width: 100%; padding: 12px 24px; background: linear-gradient(135deg, #FF5437, #FF7A63); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer;&quot;>Subscribe Free</button></form><p style=&quot;font-size: 12px; color: #9ca3af; margin-top: 12px;&quot;>No spam, unsubscribe anytime.</p></div>` },
                                'welcome_offer': { name: 'Welcome Discount', icon: 'fa-tag', color: 'green', types: ['modal', 'full_screen', 'slide_in'], html: `<div style=&quot;padding: 40px 32px; text-align: center; background: linear-gradient(135deg, #f0fdf4, #dcfce7);&quot;><div style=&quot;display: inline-block; padding: 6px 16px; background: #16a34a; color: white; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;&quot;>Limited Time Offer</div><h2 style=&quot;font-size: 28px; font-weight: 800; color: #1a1d2e; margin: 0 0 8px;&quot;>Get <span style=&quot;color: #16a34a;&quot;>30% OFF</span> Pro</h2><p style=&quot;font-size: 15px; color: #6b7280; margin: 0 0 24px; line-height: 1.6;&quot;>Unlock unlimited generations, advanced analytics, and priority support.</p><a href=&quot;/pricing&quot; style=&quot;display: inline-block; padding: 14px 32px; background: #16a34a; color: white; border-radius: 10px; font-size: 16px; font-weight: 600; text-decoration: none;&quot;>Claim Your Discount</a><p style=&quot;font-size: 13px; color: #9ca3af; margin-top: 16px;&quot;>Use code <strong style=&quot;color: #1a1d2e;&quot;>WELCOME30</strong> at checkout</p></div>` },
                                'announcement': { name: 'Feature Announcement', icon: 'fa-bullhorn', color: 'blue', types: ['modal', 'slide_in', 'full_screen'], html: `<div style=&quot;padding: 40px 32px; text-align: center;&quot;><div style=&quot;display: inline-block; padding: 6px 16px; background: #dbeafe; color: #2563eb; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;&quot;>New Feature</div><h2 style=&quot;font-size: 24px; font-weight: 700; color: #1a1d2e; margin: 0 0 8px;&quot;>Introducing AI Video Scripts</h2><p style=&quot;font-size: 15px; color: #6b7280; margin: 0 0 24px; line-height: 1.6; max-width: 420px; margin-left: auto; margin-right: auto;&quot;>Generate professional video scripts for YouTube, TikTok, and Instagram Reels in seconds with our new AI-powered tool.</p><div style=&quot;display: flex; gap: 12px; justify-content: center;&quot;><a href=&quot;/tools&quot; style=&quot;display: inline-block; padding: 12px 28px; background: #2563eb; color: white; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none;&quot;>Try It Now</a><a href=&quot;/blog&quot; style=&quot;display: inline-block; padding: 12px 28px; background: #f3f4f6; color: #374151; border-radius: 10px; font-size: 15px; font-weight: 600; text-decoration: none;&quot;>Learn More</a></div></div>` },
                                'exit_intent': { name: 'Exit Intent / Wait!', icon: 'fa-hand', color: 'amber', types: ['modal', 'full_screen'], html: `<div style=&quot;padding: 40px 32px; text-align: center;&quot;><div style=&quot;font-size: 48px; margin-bottom: 16px;&quot;>&#9888;&#65039;</div><h2 style=&quot;font-size: 26px; font-weight: 800; color: #1a1d2e; margin: 0 0 8px;&quot;>Wait, Don't Leave Yet!</h2><p style=&quot;font-size: 15px; color: #6b7280; margin: 0 0 24px; line-height: 1.6;&quot;>You haven't tried our free tools yet. Generate titles, descriptions, and more — completely free.</p><a href=&quot;/tools&quot; style=&quot;display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #FF5437, #FF7A63); color: white; border-radius: 10px; font-size: 16px; font-weight: 600; text-decoration: none;&quot;>Explore Free Tools</a><p style=&quot;font-size: 13px; color: #9ca3af; margin-top: 16px; cursor: pointer;&quot;>No thanks, I'll pass</p></div>` },
                                'announcement_bar_tpl': { name: 'Announcement Bar', icon: 'fa-bell', color: 'purple', types: ['announcement_bar', 'bottom_bar'], html: `<div style=&quot;padding: 14px 24px; display: flex; align-items: center; justify-content: center; gap: 12px; flex-wrap: wrap; background: linear-gradient(135deg, #7c3aed, #a78bfa);&quot;><span style=&quot;color: white; font-size: 14px; font-weight: 500;&quot;>&#127881; New: Instagram Reel Idea Generator is here!</span><a href=&quot;/tools/instagram/reel-idea-generator&quot; style=&quot;display: inline-block; padding: 6px 16px; background: white; color: #7c3aed; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;&quot;>Try It Free &rarr;</a></div>` },
                                'promo_bar': { name: 'Promo / Sale Bar', icon: 'fa-percent', color: 'green', types: ['announcement_bar', 'bottom_bar'], html: `<div style=&quot;padding: 12px 24px; display: flex; align-items: center; justify-content: center; gap: 10px; flex-wrap: wrap; background: #16a34a;&quot;><span style=&quot;color: white; font-size: 14px; font-weight: 600;&quot;>&#128293; Flash Sale: 50% OFF Pro Plan &mdash; Use code <span style=&quot;background: rgba(255,255,255,0.2); padding: 2px 8px; border-radius: 4px; font-family: monospace;&quot;>FLASH50</span></span><a href=&quot;/pricing&quot; style=&quot;display: inline-block; padding: 6px 16px; background: white; color: #16a34a; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none;&quot;>Upgrade Now &rarr;</a></div>` },
                                'social_proof': { name: 'Social Proof', icon: 'fa-users', color: 'indigo', types: ['modal', 'slide_in', 'full_screen'], html: `<div style=&quot;padding: 40px 32px; text-align: center;&quot;><div style=&quot;display: flex; justify-content: center; margin-bottom: 16px;&quot;><div style=&quot;width: 36px; height: 36px; border-radius: 50%; background: #e0e7ff; border: 2px solid white; display: flex; align-items: center; justify-content: center; margin-right: -8px; font-size: 14px;&quot;>&#128100;</div><div style=&quot;width: 36px; height: 36px; border-radius: 50%; background: #fce7f3; border: 2px solid white; display: flex; align-items: center; justify-content: center; margin-right: -8px; font-size: 14px;&quot;>&#128100;</div><div style=&quot;width: 36px; height: 36px; border-radius: 50%; background: #dcfce7; border: 2px solid white; display: flex; align-items: center; justify-content: center; margin-right: -8px; font-size: 14px;&quot;>&#128100;</div><div style=&quot;width: 36px; height: 36px; border-radius: 50%; background: #fef3c7; border: 2px solid white; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700; color: #92400e;&quot;>+9k</div></div><h2 style=&quot;font-size: 24px; font-weight: 700; color: #1a1d2e; margin: 0 0 8px;&quot;>Join 10,000+ Creators</h2><p style=&quot;font-size: 15px; color: #6b7280; margin: 0 0 8px; line-height: 1.6;&quot;>Content creators trust Postigniter to grow their channels.</p><div style=&quot;color: #f59e0b; font-size: 18px; margin-bottom: 20px;&quot;>&#9733;&#9733;&#9733;&#9733;&#9733; <span style=&quot;font-size: 13px; color: #9ca3af; margin-left: 4px;&quot;>4.9/5 average rating</span></div><a href=&quot;/register&quot; style=&quot;display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #FF5437, #FF7A63); color: white; border-radius: 10px; font-size: 16px; font-weight: 600; text-decoration: none;&quot;>Get Started Free</a></div>` },
                                'cookie_consent': { name: 'Cookie Consent', icon: 'fa-cookie-bite', color: 'gray', types: ['bottom_bar', 'announcement_bar', 'slide_in'], html: `<div style=&quot;padding: 20px 24px; display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap;&quot;><div style=&quot;flex: 1; min-width: 200px;&quot;><p style=&quot;font-size: 14px; color: #374151; margin: 0; line-height: 1.5;&quot;>&#127850; We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies. <a href=&quot;/cookies&quot; style=&quot;color: #FF5437; text-decoration: underline;&quot;>Learn more</a></p></div><div style=&quot;display: flex; gap: 8px; flex-shrink: 0;&quot;><button style=&quot;padding: 8px 20px; background: #f3f4f6; color: #374151; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;&quot;>Decline</button><button style=&quot;padding: 8px 20px; background: #FF5437; color: white; border: none; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;&quot;>Accept All</button></div></div>` },
                                'webinar_cta': { name: 'Webinar / Event CTA', icon: 'fa-video', color: 'rose', types: ['modal', 'full_screen'], html: `<div style=&quot;padding: 40px 32px; text-align: center; background: linear-gradient(135deg, #fff1f2, #ffe4e6);&quot;><div style=&quot;display: inline-block; padding: 6px 16px; background: #e11d48; color: white; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 16px;&quot;>Live Event</div><h2 style=&quot;font-size: 24px; font-weight: 700; color: #1a1d2e; margin: 0 0 8px;&quot;>Free Masterclass: YouTube Growth in 2026</h2><p style=&quot;font-size: 15px; color: #6b7280; margin: 0 0 8px; line-height: 1.6;&quot;>Learn the strategies top creators use to grow from 0 to 100k subscribers.</p><p style=&quot;font-size: 14px; font-weight: 600; color: #e11d48; margin: 0 0 24px;&quot;><i class=&quot;fas fa-calendar-alt&quot;></i> March 20, 2026 &bull; 2:00 PM EST</p><a href=&quot;#&quot; style=&quot;display: inline-block; padding: 14px 32px; background: #e11d48; color: white; border-radius: 10px; font-size: 16px; font-weight: 600; text-decoration: none;&quot;>Reserve My Spot</a><p style=&quot;font-size: 12px; color: #9ca3af; margin-top: 12px;&quot;>Limited to 500 attendees &bull; Free replay included</p></div>` },
                                'feedback': { name: 'Feedback Request', icon: 'fa-comment-dots', color: 'teal', types: ['modal', 'slide_in'], html: `<div style=&quot;padding: 36px 32px; text-align: center;&quot;><div style=&quot;font-size: 40px; margin-bottom: 12px;&quot;>&#128172;</div><h2 style=&quot;font-size: 22px; font-weight: 700; color: #1a1d2e; margin: 0 0 8px;&quot;>How's Your Experience?</h2><p style=&quot;font-size: 15px; color: #6b7280; margin: 0 0 20px; line-height: 1.6;&quot;>We'd love to hear your thoughts. Your feedback helps us build better tools.</p><div style=&quot;display: flex; justify-content: center; gap: 12px; margin-bottom: 24px;&quot;><button style=&quot;width: 56px; height: 56px; border-radius: 12px; border: 2px solid #e5e7eb; background: white; font-size: 24px; cursor: pointer;&quot; title=&quot;Love it&quot;>&#128525;</button><button style=&quot;width: 56px; height: 56px; border-radius: 12px; border: 2px solid #e5e7eb; background: white; font-size: 24px; cursor: pointer;&quot; title=&quot;It's okay&quot;>&#128528;</button><button style=&quot;width: 56px; height: 56px; border-radius: 12px; border: 2px solid #e5e7eb; background: white; font-size: 24px; cursor: pointer;&quot; title=&quot;Needs work&quot;>&#128533;</button></div><a href=&quot;/contact&quot; style=&quot;font-size: 13px; color: #FF5437; text-decoration: underline;&quot;>Or send detailed feedback &rarr;</a></div>` },
                                'minimal_cta': { name: 'Minimal CTA', icon: 'fa-arrow-pointer', color: 'slate', types: ['modal', 'slide_in', 'bottom_bar', 'announcement_bar'], html: `<div style=&quot;padding: 32px; text-align: center;&quot;><h2 style=&quot;font-size: 20px; font-weight: 700; color: #1a1d2e; margin: 0 0 12px;&quot;>Ready to grow your channel?</h2><p style=&quot;font-size: 14px; color: #6b7280; margin: 0 0 20px;&quot;>Start using our free tools — no signup required.</p><a href=&quot;/tools&quot; style=&quot;display: inline-block; padding: 12px 28px; background: #1a1d2e; color: white; border-radius: 8px; font-size: 14px; font-weight: 600; text-decoration: none;&quot;>Get Started &rarr;</a></div>` }
                            },
                            filteredTemplates() {
                                const type = this.popupType;
                                const result = {};
                                for (const [key, tpl] of Object.entries(this.templates)) {
                                    if (tpl.types.includes(type)) {
                                        result[key] = tpl;
                                    }
                                }
                                return result;
                            },
                            filteredCount() {
                                return Object.keys(this.filteredTemplates()).length;
                            },
                            applyTemplate(key) {
                                const tpl = this.templates[key];
                                if (!tpl) return;
                                $wire.set('content', tpl.html);
                                this.mode = 'preview';
                                this.showTemplates = false;
                            },
                            init() {
                                this.$watch('mode', (val) => {
                                    if (val === 'visual' && this.$refs.visualEditor) {
                                        this.$refs.visualEditor.innerHTML = $wire.content;
                                    }
                                });
                            },
                            updateContent() {
                                $wire.set('content', this.$refs.visualEditor.innerHTML);
                            }
                        }">
                            {{-- Mode Toggle + Template Selector --}}
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center space-x-1">
                                    <button @click="mode = 'preview'" :class="mode === 'preview' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1 text-xs font-medium rounded-lg transition">
                                        <i class="fas fa-eye mr-1"></i> Preview
                                    </button>
                                    <button @click="mode = 'visual'" :class="mode === 'visual' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1 text-xs font-medium rounded-lg transition">
                                        <i class="fas fa-pen mr-1"></i> Visual
                                    </button>
                                    <button @click="mode = 'html'" :class="mode === 'html' ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700'" class="px-3 py-1 text-xs font-medium rounded-lg transition">
                                        <i class="fas fa-code mr-1"></i> HTML
                                    </button>
                                </div>
                                {{-- Template Dropdown --}}
                                <div class="relative" @click.outside="showTemplates = false">
                                    <button @click="showTemplates = !showTemplates" type="button"
                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-medium transition">
                                        <i class="fas fa-wand-magic-sparkles mr-1.5"></i> Templates
                                        <span x-show="filteredCount() > 0" class="ml-1 px-1.5 py-0.5 bg-indigo-200 text-indigo-800 rounded-full text-[10px] font-bold" x-text="filteredCount()"></span>
                                        <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i>
                                    </button>
                                    <div x-show="showTemplates" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-100"
                                        x-transition:leave-start="opacity-100 scale-100"
                                        x-transition:leave-end="opacity-0 scale-95"
                                        class="absolute right-0 mt-2 w-72 bg-white rounded-xl shadow-xl border border-gray-200 z-50 overflow-hidden">
                                        <div class="px-3 py-2 bg-gray-50 border-b border-gray-200">
                                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Templates for <span x-text="popupType.replace('_', ' ')" class="capitalize"></span></p>
                                        </div>
                                        <div class="max-h-80 overflow-y-auto py-1">
                                            <template x-if="filteredCount() === 0">
                                                <div class="px-3 py-6 text-center text-gray-400 text-sm">
                                                    <i class="fas fa-inbox text-2xl mb-2 block"></i>
                                                    No templates for this popup type
                                                </div>
                                            </template>
                                            <template x-for="(tpl, key) in filteredTemplates()" :key="key">
                                                <button @click="applyTemplate(key)" type="button"
                                                    class="w-full text-left px-3 py-2.5 hover:bg-gray-50 flex items-center gap-3 transition-colors">
                                                    <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                                                        :class="{
                                                            'bg-indigo-100 text-indigo-600': tpl.color === 'brand',
                                                            'bg-green-100 text-green-600': tpl.color === 'green',
                                                            'bg-blue-100 text-blue-600': tpl.color === 'blue',
                                                            'bg-amber-100 text-amber-600': tpl.color === 'amber',
                                                            'bg-purple-100 text-purple-600': tpl.color === 'purple',
                                                            'bg-indigo-100 text-indigo-600': tpl.color === 'indigo',
                                                            'bg-gray-100 text-gray-600': tpl.color === 'gray',
                                                            'bg-rose-100 text-rose-600': tpl.color === 'rose',
                                                            'bg-teal-100 text-teal-600': tpl.color === 'teal',
                                                            'bg-slate-100 text-slate-600': tpl.color === 'slate',
                                                        }">
                                                        <i class="fas" :class="tpl.icon" style="font-size: 13px;"></i>
                                                    </div>
                                                    <span class="text-sm font-medium text-gray-700" x-text="tpl.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="px-3 py-2 bg-gray-50 border-t border-gray-200">
                                            <p class="text-[11px] text-gray-400">Selecting a template replaces current content</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Preview Mode --}}
                            <div x-show="mode === 'preview'" class="border border-gray-300 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 border-b border-gray-200 px-3 py-2 flex items-center justify-between">
                                    <span class="text-xs text-gray-500 font-medium"><i class="fas fa-eye mr-1"></i> Live Preview</span>
                                    <span class="text-[11px] text-gray-400">Click "Visual" or "HTML" to edit</span>
                                </div>
                                <div class="bg-white min-h-[200px] max-h-[350px] overflow-y-auto">
                                    <template x-if="$wire.content">
                                        <div x-html="$wire.content"></div>
                                    </template>
                                    <template x-if="!$wire.content">
                                        <div class="flex flex-col items-center justify-center py-16 text-gray-400">
                                            <i class="fas fa-image text-3xl mb-3"></i>
                                            <p class="text-sm font-medium">No content yet</p>
                                            <p class="text-xs mt-1">Select a template or switch to Visual/HTML editor</p>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            {{-- Visual Editor --}}
                            <div x-show="mode === 'visual'" x-cloak class="border border-gray-300 rounded-lg overflow-hidden">
                                {{-- Toolbar --}}
                                <div class="bg-gray-50 border-b border-gray-200 px-3 py-2 flex flex-wrap gap-1">
                                    <button type="button" @click="document.execCommand('bold')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Bold"><i class="fas fa-bold text-xs"></i></button>
                                    <button type="button" @click="document.execCommand('italic')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Italic"><i class="fas fa-italic text-xs"></i></button>
                                    <button type="button" @click="document.execCommand('underline')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Underline"><i class="fas fa-underline text-xs"></i></button>
                                    <div class="w-px bg-gray-300 mx-1"></div>
                                    <button type="button" @click="document.execCommand('justifyLeft')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Align Left"><i class="fas fa-align-left text-xs"></i></button>
                                    <button type="button" @click="document.execCommand('justifyCenter')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Align Center"><i class="fas fa-align-center text-xs"></i></button>
                                    <button type="button" @click="document.execCommand('justifyRight')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Align Right"><i class="fas fa-align-right text-xs"></i></button>
                                    <div class="w-px bg-gray-300 mx-1"></div>
                                    <button type="button" @click="document.execCommand('insertUnorderedList')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Bullet List"><i class="fas fa-list-ul text-xs"></i></button>
                                    <button type="button" @click="document.execCommand('insertOrderedList')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Numbered List"><i class="fas fa-list-ol text-xs"></i></button>
                                    <div class="w-px bg-gray-300 mx-1"></div>
                                    <button type="button" @click="window.tcModal.prompt('Enter link URL:', {title:'Insert Link', confirmText:'Insert'}).then(url => { if (url) document.execCommand('createLink', false, url); })" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Insert Link"><i class="fas fa-link text-xs"></i></button>
                                    <button type="button" @click="document.execCommand('removeFormat')" class="p-1.5 rounded hover:bg-gray-200 text-gray-600" title="Clear Formatting"><i class="fas fa-eraser text-xs"></i></button>
                                    <div class="w-px bg-gray-300 mx-1"></div>
                                    <select @change="document.execCommand('formatBlock', false, $event.target.value); $event.target.value = ''" class="text-xs border border-gray-200 rounded px-2 py-1 bg-white">
                                        <option value="">Heading</option>
                                        <option value="h1">Heading 1</option>
                                        <option value="h2">Heading 2</option>
                                        <option value="h3">Heading 3</option>
                                        <option value="p">Paragraph</option>
                                    </select>
                                </div>
                                {{-- Editable Area --}}
                                <div x-ref="visualEditor" contenteditable="true"
                                    @input="updateContent()"
                                    @blur="updateContent()"
                                    class="p-4 min-h-[200px] max-h-[300px] overflow-y-auto focus:outline-none prose prose-sm max-w-none"
                                    x-init="$refs.visualEditor.innerHTML = $wire.content"
                                ></div>
                            </div>

                            {{-- HTML Editor --}}
                            <div x-show="mode === 'html'" x-cloak>
                                <textarea wire:model.live.debounce.500ms="content" rows="10" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 font-mono text-sm" placeholder="<div class='p-6 text-center'>&#10;  <h2>Your Popup Content</h2>&#10;  <p>Add your message here...</p>&#10;</div>"></textarea>
                            </div>
                        </div>
                        @error('content') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        <p class="text-xs text-gray-500 mt-1">Design your popup content using the visual editor or write HTML directly. You can use Tailwind CSS classes.</p>
                    </div>

                    {{-- Schedule --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Schedule (optional)</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">Start Date</label>
                                <input wire:model="starts_at" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 text-sm">
                                @error('starts_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="block text-xs text-gray-500 mb-1">End Date</label>
                                <input wire:model="ends_at" type="datetime-local" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 text-sm">
                                @error('ends_at') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Leave empty to show the popup indefinitely (when active).</p>
                    </div>
                </div>
                @endif

                {{-- ===== TRIGGER TAB ===== --}}
                @if($activeTab === 'trigger')
                <div class="space-y-6">
                    {{-- Trigger Type --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Trigger Type</label>
                        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                            @foreach($triggerTypes as $value => $label)
                            <button wire:click="$set('trigger_type', '{{ $value }}')" type="button"
                                class="flex flex-col items-center justify-center p-4 border-2 rounded-xl transition-all {{ $trigger_type === $value ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}">
                                @if($value === 'click') <i class="fas fa-mouse-pointer text-xl mb-2"></i>
                                @elseif($value === 'exit_intent') <i class="fas fa-right-from-bracket text-xl mb-2"></i>
                                @elseif($value === 'scroll') <i class="fas fa-arrow-down text-xl mb-2"></i>
                                @elseif($value === 'timed') <i class="fas fa-clock text-xl mb-2"></i>
                                @elseif($value === 'page_load') <i class="fas fa-rotate text-xl mb-2"></i>
                                @endif
                                <span class="text-sm font-medium">{{ $label }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Trigger Value --}}
                    @if(in_array($trigger_type, ['scroll', 'timed', 'click']))
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">
                            @if($trigger_type === 'scroll') Scroll Percentage (%)
                            @elseif($trigger_type === 'timed') Delay (seconds)
                            @elseif($trigger_type === 'click') CSS Selector
                            @endif
                        </label>
                        <input wire:model="trigger_value" type="{{ $trigger_type === 'click' ? 'text' : 'number' }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600"
                            placeholder="{{ $trigger_type === 'scroll' ? '50' : ($trigger_type === 'timed' ? '5' : '#my-button, .popup-trigger') }}">
                        <p class="text-xs text-gray-500 mt-1">
                            @if($trigger_type === 'scroll') Show popup when user scrolls past this percentage of the page.
                            @elseif($trigger_type === 'timed') Show popup after this many seconds on the page.
                            @elseif($trigger_type === 'click') CSS selector of the element(s) that should trigger this popup when clicked.
                            @endif
                        </p>
                    </div>
                    @endif

                    {{-- Frequency --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Frequency</label>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Don't Show Again For (Days)</label>
                            <input wire:model="frequency_days" type="number" min="0" class="w-full max-w-xs px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                            <p class="text-xs text-gray-500 mt-1">0 = show every visit</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ===== DISPLAY RULES TAB ===== --}}
                @if($activeTab === 'display')
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {{-- Display On --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Display On</label>
                            <select wire:model.live="display_on" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                                <option value="all_pages">All Pages</option>
                                <option value="specific_pages">Specific Pages</option>
                            </select>
                        </div>

                        {{-- Device --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Device</label>
                            <select wire:model="device" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                                @foreach($devices as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Visibility --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Visibility</label>
                            <select wire:model="visibility" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                                @foreach($visibilities as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Frontend = public site, Backend = admin/dashboard area</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Priority --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Priority</label>
                            <input wire:model="priority" type="number" min="0" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                            <p class="text-xs text-gray-500 mt-1">Higher number = appears on top when multiple popups load. Default: 10</p>
                        </div>

                        {{-- Show On Pages --}}
                        @if($display_on === 'specific_pages')
                        <div x-data="{ showOnFocused: false }" @click.outside="showOnFocused = false">
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Show On Pages</label>
                            <div class="relative">
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <input wire:model.live.debounce.300ms="showOnPageInput" type="text"
                                            @focus="showOnFocused = true"
                                            @keydown.enter.prevent="$wire.addShowOnPage()"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 text-sm"
                                            placeholder="Search or type path with wildcard (e.g. /tools/*)">
                                        {{-- Dropdown Results --}}
                                        @if(!empty($showOnPageResults))
                                        <div x-show="showOnFocused" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                            @foreach($showOnPageResults as $result)
                                            <button type="button"
                                                wire:click="addShowOnPage('{{ $result['path'] }}')"
                                                @click="showOnFocused = false"
                                                class="w-full text-left px-3 py-2 hover:bg-indigo-50 text-sm flex items-center gap-2 transition-colors">
                                                @if($result['type'] === 'wildcard')
                                                    <i class="fas fa-asterisk text-amber-500 text-xs w-4"></i>
                                                @elseif($result['type'] === 'post')
                                                    <i class="fas fa-newspaper text-blue-500 text-xs w-4"></i>
                                                @else
                                                    <i class="fas fa-file text-gray-400 text-xs w-4"></i>
                                                @endif
                                                <span class="truncate">{{ $result['label'] }}</span>
                                            </button>
                                            @endforeach
                                        </div>
                                        @endif
                                    </div>
                                    <button type="button" wire:click="addShowOnPage" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">Add</button>
                                </div>
                            </div>
                            @if(!empty($show_on_pages))
                            <div class="flex flex-wrap gap-2 mt-2">
                                @foreach($show_on_pages as $idx => $page)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm">
                                    {{ $page }}
                                    <button type="button" wire:click="removeShowOnPage({{ $idx }})" class="text-indigo-600 hover:text-indigo-800"><i class="fas fa-times text-xs"></i></button>
                                </span>
                                @endforeach
                            </div>
                            @endif
                            <p class="text-xs text-gray-500 mt-1">Search for pages or type a path with wildcards. Use <code class="bg-gray-100 px-1 rounded">/tools/*</code> to match all pages under /tools/.</p>
                        </div>
                        @endif
                    </div>

                    {{-- Exclude Pages --}}
                    <div x-data="{ excludeFocused: false }" @click.outside="excludeFocused = false">
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Exclude Pages</label>
                        <div class="relative">
                            <div class="flex gap-2">
                                <div class="relative flex-1">
                                    <input wire:model.live.debounce.300ms="excludePageInput" type="text"
                                        @focus="excludeFocused = true"
                                        @keydown.enter.prevent="$wire.addExcludePage()"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 text-sm"
                                        placeholder="Search or type path with wildcard (e.g. /perks/*)">
                                    {{-- Dropdown Results --}}
                                    @if(!empty($excludePageResults))
                                    <div x-show="excludeFocused" x-cloak class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto">
                                        @foreach($excludePageResults as $result)
                                        <button type="button"
                                            wire:click="addExcludePage('{{ $result['path'] }}')"
                                            @click="excludeFocused = false"
                                            class="w-full text-left px-3 py-2 hover:bg-red-50 text-sm flex items-center gap-2 transition-colors">
                                            @if($result['type'] === 'wildcard')
                                                <i class="fas fa-asterisk text-amber-500 text-xs w-4"></i>
                                            @elseif($result['type'] === 'post')
                                                <i class="fas fa-newspaper text-blue-500 text-xs w-4"></i>
                                            @else
                                                <i class="fas fa-file text-gray-400 text-xs w-4"></i>
                                            @endif
                                            <span class="truncate">{{ $result['label'] }}</span>
                                        </button>
                                        @endforeach
                                    </div>
                                    @endif
                                </div>
                                <button type="button" wire:click="addExcludePage" class="px-3 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm font-medium transition">Add</button>
                            </div>
                        </div>
                        @if(!empty($exclude_pages))
                        <div class="flex flex-wrap gap-2 mt-2">
                            @foreach($exclude_pages as $idx => $page)
                            <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-sm">
                                {{ $page }}
                                <button type="button" wire:click="removeExcludePage({{ $idx }})" class="text-red-500 hover:text-red-800"><i class="fas fa-times text-xs"></i></button>
                            </span>
                            @endforeach
                        </div>
                        @endif
                        <p class="text-xs text-gray-500 mt-1">Excluded pages take priority over show rules. Use <code class="bg-gray-100 px-1 rounded">/perks/*</code> to exclude all pages under /perks/.</p>
                    </div>
                </div>
                @endif

                {{-- ===== STYLE TAB ===== --}}
                @if($activeTab === 'style')
                <div class="space-y-6">
                    {{-- Position --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Position</label>
                        <div class="grid grid-cols-3 sm:grid-cols-6 gap-3">
                            @foreach($positions as $value => $label)
                            <button wire:click="$set('position', '{{ $value }}')" type="button"
                                class="flex flex-col items-center justify-center p-3 border-2 rounded-xl transition-all {{ $position === $value ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}">
                                <div class="w-8 h-8 border border-current rounded mb-1 relative flex items-center justify-center">
                                    @if($value === 'center') <div class="w-3 h-3 bg-current rounded-sm"></div>
                                    @elseif($value === 'top') <div class="w-full h-2 bg-current rounded-sm absolute top-0"></div>
                                    @elseif($value === 'bottom') <div class="w-full h-2 bg-current rounded-sm absolute bottom-0"></div>
                                    @elseif($value === 'left') <div class="h-full w-2 bg-current rounded-sm absolute left-0"></div>
                                    @elseif($value === 'right') <div class="h-full w-2 bg-current rounded-sm absolute right-0"></div>
                                    @elseif($value === 'full') <div class="w-full h-full bg-current rounded-sm opacity-50"></div>
                                    @endif
                                </div>
                                <span class="text-xs font-medium">{{ $label }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Dimensions --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Dimensions</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Width (px)</label>
                                <input wire:model="width_px" type="number" min="200" max="2000" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Max Width (% Viewport)</label>
                                <input wire:model="max_width_vw" type="number" min="10" max="100" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600">
                            </div>
                        </div>
                    </div>

                    {{-- Animation --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Animation</label>
                        <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                            @foreach($animations as $value => $label)
                            <button wire:click="$set('animation', '{{ $value }}')" type="button"
                                class="px-4 py-2.5 border-2 rounded-xl text-sm font-medium transition-all {{ $animation === $value ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}">
                                {{ $label }}
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Overlay & Close --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Overlay & Close</label>
                        <div class="flex flex-wrap gap-6">
                            <div class="flex items-center space-x-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input wire:model="show_overlay" type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-700">Show Overlay</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input wire:model="close_on_overlay_click" type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-700">Close on Overlay Click</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input wire:model="show_close_button" type="checkbox" class="sr-only peer">
                                    <div class="w-11 h-6 bg-gray-200 peer-focus:ring-4 peer-focus:ring-indigo-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-indigo-600"></div>
                                </label>
                                <span class="text-sm font-medium text-gray-700">Show Close Button</span>
                            </div>
                        </div>
                    </div>

                    {{-- Shadow --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Shadow</label>
                        <div class="grid grid-cols-5 gap-3">
                            @foreach($shadows as $value => $label)
                            <button wire:click="$set('shadow', '{{ $value }}')" type="button"
                                class="flex flex-col items-center justify-center p-3 border-2 rounded-xl transition-all {{ $shadow === $value ? 'border-indigo-600 bg-indigo-50 text-indigo-700' : 'border-gray-200 hover:border-gray-300 text-gray-600' }}">
                                <div class="w-10 h-10 bg-white rounded border border-gray-200 mb-1
                                    {{ $value === 'sm' ? 'shadow-sm' : ($value === 'md' ? 'shadow-md' : ($value === 'lg' ? 'shadow-lg' : ($value === 'xl' ? 'shadow-xl' : ''))) }}"></div>
                                <span class="text-xs font-medium">{{ $label }}</span>
                            </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Border --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Border</label>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Border Radius</label>
                                <div class="flex">
                                    <input wire:model="border_radius" type="number" min="0" max="100" class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-indigo-600 focus:border-indigo-600">
                                    <span class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-sm text-gray-500">px</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Border Width</label>
                                <div class="flex">
                                    <input wire:model="border_width" type="number" min="0" max="20" class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-indigo-600 focus:border-indigo-600">
                                    <span class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-sm text-gray-500">px</span>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Border Color</label>
                                <div class="flex items-center gap-2">
                                    <input wire:model="border_color" type="color" class="w-8 h-8 rounded cursor-pointer border border-gray-300">
                                    <input wire:model="border_color" type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Padding</label>
                                <div class="flex">
                                    <input wire:model="padding" type="number" min="0" max="200" class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg focus:ring-indigo-600 focus:border-indigo-600">
                                    <span class="px-3 py-2 bg-gray-100 border border-l-0 border-gray-300 rounded-r-lg text-sm text-gray-500">px</span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Inner padding around popup content</p>
                            </div>
                        </div>
                    </div>

                    {{-- Colors --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Colors</label>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Background Color</label>
                                <div class="flex items-center gap-2">
                                    <input wire:model="bg_color" type="color" class="w-8 h-8 rounded cursor-pointer border border-gray-300">
                                    <input wire:model="bg_color" type="text" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 text-sm">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Overlay Color</label>
                                <input wire:model="overlay_color" type="text" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-600 focus:border-indigo-600 text-sm" placeholder="rgba(0,0,0,0.6)">
                                <p class="text-xs text-gray-500 mt-1">Supports rgba for transparency, e.g. rgba(0,0,0,0.6)</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gray-50 rounded-b-xl flex justify-end space-x-3 flex-shrink-0">
                <button wire:click="closeModal" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-sm transition">
                    Cancel
                </button>
                <button wire:click="save" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-500 font-medium text-sm transition">
                    <span wire:loading.remove wire:target="save">{{ $editingPopupId ? 'Update Popup' : 'Create Popup' }}</span>
                    <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin mr-1"></i> Saving...</span>
                </button>
            </div>
        </div>
        </div>
    </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    @if($showDeleteConfirm)
    <div class="fixed inset-0 z-50">
        <div class="fixed inset-0 bg-black/50"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6 text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete Popup</h3>
                <p class="text-gray-600 text-sm mb-6">Are you sure you want to delete this popup? This action cannot be undone.</p>
                <div class="flex justify-center space-x-3">
                    <button wire:click="cancelDelete" class="px-4 py-2 text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 font-medium text-sm transition">
                        Cancel
                    </button>
                    <button wire:click="deletePopup" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-medium text-sm transition">
                        <span wire:loading.remove wire:target="deletePopup">Delete</span>
                        <span wire:loading wire:target="deletePopup"><i class="fas fa-spinner fa-spin mr-1"></i> Deleting...</span>
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>
    @endif
</div>

<div class="max-w-3xl mx-auto" id="comments">
    {{-- Comment Count Header --}}
    <div class="border-t border-gray-200 dark:border-gray-700 pt-10 mb-8">
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white">
            {{ $this->totalCount }} {{ Str::plural('Comment', $this->totalCount) }}
        </h3>
    </div>

    {{-- Success Message --}}
    @if($successMessage)
        <div class="mb-6 rounded-md bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
            <div class="flex">
                <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                </svg>
                <p class="ml-3 text-sm text-green-700 dark:text-green-300">{{ $successMessage }}</p>
            </div>
        </div>
    @endif

    {{-- Comment Form --}}
    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-6 mb-10">
        @guest
            {{-- Guest: prompt to login --}}
            <div class="text-center py-4">
                <svg class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z"/>
                </svg>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">Log in to join the conversation.</p>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-primary text-white text-sm font-semibold rounded-md hover:bg-primary-hover transition-colors">
                    Login to Comment
                </a>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                    Don't have an account? <a href="{{ route('register') }}" class="text-primary hover:text-primary-hover font-medium">Register</a>
                </p>
            </div>
        @else
            {{-- Authenticated user --}}
            <div class="flex items-center gap-3 mb-4">
                <img src="{{ auth()->user()->avatar_url }}" alt="{{ auth()->user()->name }}"
                     class="w-8 h-8 rounded-full object-cover">
                <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
            </div>

            @if(!auth()->user()->hasVerifiedEmail())
                <div class="mb-4 rounded-md bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-3">
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        Please <a href="{{ route('verification.notice') }}" class="underline font-semibold hover:text-amber-900 dark:hover:text-amber-100">verify your email address</a> before commenting.
                    </p>
                </div>
            @endif

            @error('recaptcha')
                <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-3">
                    <p class="text-sm text-red-700 dark:text-red-300">{{ $message }}</p>
                </div>
            @enderror

            <form x-data="{ siteKey: '{{ $this->getRecaptchaSiteKey() }}' }"
                  x-on:submit.prevent="
                      if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                          grecaptcha.enterprise.ready(async () => {
                              try {
                                  const token = await grecaptcha.enterprise.execute(siteKey, { action: 'comment' });
                                  $wire.set('recaptchaToken', token);
                              } catch (e) {
                                  $wire.set('recaptchaToken', 'RECAPTCHA_FAILED');
                              }
                              $wire.submitComment();
                          });
                      } else if (siteKey) {
                          $wire.set('recaptchaToken', 'RECAPTCHA_NOT_LOADED');
                          $wire.submitComment();
                      } else {
                          $wire.submitComment();
                      }
                  ">
                {{-- Honeypot --}}
                <div class="hidden" aria-hidden="true">
                    <input type="text" wire:model="honeypot" tabindex="-1" autocomplete="off">
                </div>

                <div class="mb-4">
                    <textarea id="comment-body" wire:model="body" rows="3"
                              class="block w-full rounded-md border-0 py-2 text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary sm:text-sm"
                              placeholder="Write your comment..."
                              {{ !auth()->user()->hasVerifiedEmail() ? 'disabled' : '' }}></textarea>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end">
                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-semibold rounded-md hover:bg-primary-hover transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                            wire:loading.attr="disabled"
                            {{ !auth()->user()->hasVerifiedEmail() ? 'disabled' : '' }}>
                        <span wire:loading.remove wire:target="submitComment">Post Comment</span>
                        <span wire:loading wire:target="submitComment">Posting...</span>
                    </button>
                </div>
            </form>
        @endguest
    </div>

    {{-- Comments List --}}
    <div class="space-y-8">
        @foreach($this->comments as $comment)
            <div class="flex gap-4" id="comment-{{ $comment->id }}">
                {{-- Avatar --}}
                <div class="flex-shrink-0">
                    <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($comment->user?->email ?? $comment->guest_email ?? ''))) }}?s=80&d=mp"
                         alt="{{ $comment->author_name }}"
                         class="w-10 h-10 rounded-full">
                </div>

                <div class="flex-1 min-w-0">
                    {{-- Comment Header --}}
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $comment->author_name }}</span>
                        <span class="text-xs text-gray-400 dark:text-gray-500">&middot;</span>
                        <time class="text-xs text-gray-400 dark:text-gray-500" datetime="{{ $comment->created_at->toIso8601String() }}">
                            {{ $comment->created_at->diffForHumans() }}
                        </time>
                    </div>

                    {{-- Comment Body --}}
                    <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-2">
                        {!! nl2br(e($comment->body)) !!}
                    </div>

                    {{-- Reply Button (auth only) --}}
                    @auth
                        <button wire:click="startReply({{ $comment->id }})"
                                class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">
                            Reply
                        </button>

                        {{-- Inline Reply Form --}}
                        @if($replyingTo === $comment->id)
                            <div class="mt-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                <form x-data="{ siteKey: '{{ $this->getRecaptchaSiteKey() }}' }"
                                      x-on:submit.prevent="
                                          if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                                              grecaptcha.enterprise.ready(async () => {
                                                  try {
                                                      const token = await grecaptcha.enterprise.execute(siteKey, { action: 'comment_reply' });
                                                      $wire.set('recaptchaToken', token);
                                                  } catch (e) {
                                                      $wire.set('recaptchaToken', 'RECAPTCHA_FAILED');
                                                  }
                                                  $wire.submitReply();
                                              });
                                          } else if (siteKey) {
                                              $wire.set('recaptchaToken', 'RECAPTCHA_NOT_LOADED');
                                              $wire.submitReply();
                                          } else {
                                              $wire.submitReply();
                                          }
                                      ">
                                    <div class="hidden" aria-hidden="true">
                                        <input type="text" wire:model="replyHoneypot" tabindex="-1" autocomplete="off">
                                    </div>

                                    <div class="mb-3">
                                        <textarea wire:model="replyBody" rows="3"
                                                  class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary"
                                                  placeholder="Write your reply..."></textarea>
                                        @error('replyBody')
                                            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div class="flex items-center gap-2 justify-end">
                                        <button type="button" wire:click="cancelReply"
                                                class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-md hover:bg-primary-hover transition-colors"
                                                wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="submitReply">Post Reply</span>
                                            <span wire:loading wire:target="submitReply">Posting...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    @endauth

                    {{-- Nested Replies --}}
                    @if($comment->replies->isNotEmpty())
                        <div class="mt-4 space-y-6 pl-6 border-l-2 border-gray-200 dark:border-gray-700">
                            @foreach($comment->replies as $reply)
                                <div class="flex gap-3" id="comment-{{ $reply->id }}">
                                    <div class="flex-shrink-0">
                                        <img src="https://www.gravatar.com/avatar/{{ md5(strtolower(trim($reply->user?->email ?? $reply->guest_email ?? ''))) }}?s=64&d=mp"
                                             alt="{{ $reply->author_name }}"
                                             class="w-8 h-8 rounded-full">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $reply->author_name }}</span>
                                            <span class="text-xs text-gray-400 dark:text-gray-500">&middot;</span>
                                            <time class="text-xs text-gray-400 dark:text-gray-500" datetime="{{ $reply->created_at->toIso8601String() }}">
                                                {{ $reply->created_at->diffForHumans() }}
                                            </time>
                                        </div>
                                        <div class="text-sm text-gray-700 dark:text-gray-300 leading-relaxed mb-2">
                                            {!! nl2br(e($reply->body)) !!}
                                        </div>
                                        @auth
                                            <button wire:click="startReply({{ $reply->id }})"
                                                    class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-primary dark:hover:text-primary transition-colors">
                                                Reply
                                            </button>

                                            {{-- Inline Reply Form for reply --}}
                                            @if($replyingTo === $reply->id)
                                                <div class="mt-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4">
                                                    <form x-data="{ siteKey: '{{ $this->getRecaptchaSiteKey() }}' }"
                                                          x-on:submit.prevent="
                                                              if (siteKey && typeof grecaptcha !== 'undefined' && grecaptcha.enterprise) {
                                                                  grecaptcha.enterprise.ready(async () => {
                                                                      try {
                                                                          const token = await grecaptcha.enterprise.execute(siteKey, { action: 'comment_reply' });
                                                                          $wire.set('recaptchaToken', token);
                                                                      } catch (e) {
                                                                          $wire.set('recaptchaToken', 'RECAPTCHA_FAILED');
                                                                      }
                                                                      $wire.submitReply();
                                                                  });
                                                              } else if (siteKey) {
                                                                  $wire.set('recaptchaToken', 'RECAPTCHA_NOT_LOADED');
                                                                  $wire.submitReply();
                                                              } else {
                                                                  $wire.submitReply();
                                                              }
                                                          ">
                                                        <div class="hidden" aria-hidden="true">
                                                            <input type="text" wire:model="replyHoneypot" tabindex="-1" autocomplete="off">
                                                        </div>

                                                        <div class="mb-3">
                                                            <textarea wire:model="replyBody" rows="2"
                                                                      class="block w-full rounded-md border-0 py-1.5 text-sm text-gray-900 dark:text-white dark:bg-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-primary"
                                                                      placeholder="Write your reply..."></textarea>
                                                            @error('replyBody')
                                                                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                                                            @enderror
                                                        </div>

                                                        <div class="flex items-center gap-2 justify-end">
                                                            <button type="button" wire:click="cancelReply"
                                                                    class="text-xs font-medium text-gray-500 dark:text-gray-400 hover:text-gray-700">
                                                                Cancel
                                                            </button>
                                                            <button type="submit"
                                                                    class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-xs font-semibold rounded-md hover:bg-primary-hover transition-colors"
                                                                    wire:loading.attr="disabled">
                                                                <span wire:loading.remove wire:target="submitReply">Post Reply</span>
                                                                <span wire:loading wire:target="submitReply">Posting...</span>
                                                            </button>
                                                        </div>
                                                    </form>
                                                </div>
                                            @endif
                                        @endauth
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>

    {{-- Load More --}}
    @if($this->hasMore)
        <div class="mt-8 text-center">
            <button wire:click="loadMore"
                    class="inline-flex items-center px-6 py-2.5 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
                    wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="loadMore">Load More Comments</span>
                <span wire:loading wire:target="loadMore">Loading...</span>
            </button>
        </div>
    @endif

    {{-- Empty State --}}
    @if($this->totalCount === 0)
        <p class="text-center text-sm text-gray-500 dark:text-gray-400 py-8">
            No comments yet. Be the first to share your thoughts!
        </p>
    @endif
</div>

<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component {
    public bool $sent = false;
    public string $error = '';

    public function resend(): void
    {
        $this->error = '';
        $user = Auth::user();

        if (!$user || $user->hasVerifiedEmail()) {
            return;
        }

        // Throttle: one resend per minute per user
        $key = 'verify-resend:' . $user->id;
        if (cache()->has($key)) {
            $this->error = 'Please wait a minute before requesting another link.';
            return;
        }
        cache()->put($key, true, now()->addMinute());

        $user->sendEmailVerificationNotification();
        $this->sent = true;
    }
}; ?>

<div>
    @auth
        @if(!auth()->user()->hasVerifiedEmail())
            <div class="max-w-container mx-auto px-4 mt-4">
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-2.5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div class="flex items-start sm:items-center gap-2.5 text-sm text-amber-900 dark:text-amber-200">
                        <svg class="w-5 h-5 shrink-0 mt-0.5 sm:mt-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/>
                        </svg>
                        <span>
                            @if($sent)
                                <strong>Verification email sent.</strong> Check your inbox (and spam folder) for the link.
                            @else
                                Please verify your email address to unlock commenting and profile submission. We sent a link to <strong>{{ auth()->user()->email }}</strong>.
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-3 shrink-0">
                        @if($error)
                            <span class="text-xs text-amber-800 dark:text-amber-300">{{ $error }}</span>
                        @endif
                        @unless($sent)
                            <button type="button" wire:click="resend" wire:loading.attr="disabled"
                                    class="inline-flex items-center justify-center rounded-md bg-amber-600 hover:bg-amber-700 disabled:opacity-60 px-3 py-1.5 text-xs font-semibold text-white transition">
                                <span wire:loading.remove wire:target="resend">Resend link</span>
                                <span wire:loading wire:target="resend">Sending…</span>
                            </button>
                        @endunless
                    </div>
                </div>
            </div>
        @endif
    @endauth
</div>

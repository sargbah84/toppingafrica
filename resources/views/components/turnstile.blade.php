{{--
    Cloudflare Turnstile widget.

    Plain HTML forms: drop <x-turnstile action="..." /> inside the <form>; the
    widget injects a hidden `cf-turnstile-response` input that VerifiesTurnstile
    reads. Livewire forms: pass :livewire="true" and use the HasTurnstile trait;
    the token is written to $turnstileToken and the widget re-solves whenever
    the component dispatches `turnstile-reset` (tokens are single-use).

    appearance=interaction-only keeps the widget invisible unless Cloudflare
    actually needs the visitor to click something.
--}}
@props(['action', 'livewire' => false])

@php($turnstileSiteKey = app(\App\Services\TurnstileService::class)->getSiteKey())

@if($turnstileSiteKey)
    <div wire:ignore
         x-data="{ widgetId: null, errorCode: null }"
         x-init="
            window.turnstileReady ??= new Promise(resolve => {
                window.onTurnstileLoad = resolve;
                const script = document.createElement('script');
                script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=onTurnstileLoad';
                script.async = true;
                document.head.appendChild(script);
            });
            await window.turnstileReady;
            widgetId = turnstile.render($refs.widget, {
                sitekey: @js($turnstileSiteKey),
                action: @js($action),
                appearance: 'interaction-only',
                {{-- Surface failures (e.g. 110200 = hostname not allowed for this site key) instead of silently submitting without a token. --}}
                'error-callback': code => { errorCode = code; console.warn('Turnstile error', code); return true; },
                callback: token => {
                    errorCode = null;
                    @if($livewire) $wire.set('turnstileToken', token, false); @endif
                },
                @if($livewire)
                    'expired-callback': () => $wire.set('turnstileToken', '', false),
                @endif
            });
         "
         @if($livewire)
             x-on:turnstile-reset.window="try { widgetId !== null && turnstile.reset(widgetId) } catch (e) {}"
         @endif
         {{ $attributes }}>
        <div x-ref="widget"></div>
        <p x-show="errorCode" x-cloak class="text-xs text-red-600 dark:text-red-400">
            The security check couldn't load (error <span x-text="errorCode"></span>). Please refresh the page or pause any ad or privacy blocker.
        </p>
    </div>
@endif

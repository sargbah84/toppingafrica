@php
    $popupData = $popups->map(function ($popup) {
        return [
            'id' => $popup->id,
            'slug' => $popup->slug,
            'type' => $popup->type,
            'content' => $popup->content,
            'trigger_type' => $popup->trigger_type,
            'trigger_value' => $popup->trigger_value,
            'frequency_days' => $popup->frequency_days,
            'position' => $popup->position,
            'width_px' => $popup->width_px,
            'max_width_vw' => $popup->max_width_vw,
            'animation' => $popup->animation,
            'show_overlay' => $popup->show_overlay,
            'close_on_overlay_click' => $popup->close_on_overlay_click,
            'show_close_button' => $popup->show_close_button,
            'shadow' => $popup->shadow,
            'border_radius' => $popup->border_radius,
            'border_width' => $popup->border_width,
            'border_color' => $popup->border_color,
            'padding' => $popup->padding,
            'bg_color' => $popup->bg_color,
            'overlay_color' => $popup->overlay_color,
            'cookie_name' => $popup->getCookieName(),
            'priority' => $popup->priority,
        ];
    })->values();
@endphp

<style>
    /* Popup animations */
    @keyframes popupFadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes popupFadeOut { from { opacity: 1; } to { opacity: 0; } }
    @keyframes popupSlideDown { from { opacity: 0; transform: translateY(-40px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes popupSlideDownOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(-40px); } }
    @keyframes popupSlideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes popupSlideUpOut { from { opacity: 1; transform: translateY(0); } to { opacity: 0; transform: translateY(40px); } }
    @keyframes popupSlideLeft { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes popupSlideLeftOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(40px); } }
    @keyframes popupSlideRight { from { opacity: 0; transform: translateX(-40px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes popupSlideRightOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(-40px); } }
    @keyframes popupZoomIn { from { opacity: 0; transform: scale(0.8); } to { opacity: 1; transform: scale(1); } }
    @keyframes popupZoomInOut { from { opacity: 1; transform: scale(1); } to { opacity: 0; transform: scale(0.8); } }
    @keyframes popupOverlayIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes popupOverlayOut { from { opacity: 1; } to { opacity: 0; } }
</style>

<div x-data="popupEngine({{ Js::from($popupData) }})" x-init="init()" x-cloak>
    @foreach($popups as $popup)
        <div x-show="isVisible({{ $popup->id }})" class="fixed inset-0" style="z-index: {{ 9000 + ($popup->priority ?? 0) }}">
            {{-- Overlay --}}
            @if($popup->show_overlay)
                <div @click="{{ $popup->close_on_overlay_click ? 'dismissPopup(getPopup('.$popup->id.'))' : '' }}"
                    class="fixed inset-0"
                    :style="'background-color: {{ $popup->overlay_color }}; animation: ' + (isDismissing({{ $popup->id }}) ? 'popupOverlayOut' : 'popupOverlayIn') + ' 0.3s ease forwards'">
                </div>
            @endif

            {{-- Popup Container --}}
            <div @click.self="{{ $popup->close_on_overlay_click ? 'dismissPopup(getPopup('.$popup->id.'))' : '' }}"
                class="fixed {{ match($popup->position ?? 'center') {
                    'center' => 'inset-0 flex items-center justify-center p-4',
                    'top' => 'top-0 left-0 right-0 flex justify-center p-4',
                    'bottom' => 'bottom-0 left-0 right-0 flex justify-center p-4',
                    'left' => 'inset-y-0 left-0 flex items-center p-4',
                    'right' => 'inset-y-0 right-0 flex items-center justify-end p-4',
                    'full' => 'inset-0 flex items-center justify-center',
                    default => 'inset-0 flex items-center justify-center p-4',
                } }}">

                <div :style="getPopupStyles(getPopup({{ $popup->id }})) + '; animation: ' + getAnimation(getPopup({{ $popup->id }})) + ';'" class="relative">
                    {{-- Close Button --}}
                    @if($popup->show_close_button)
                        <button @click="dismissPopup(getPopup({{ $popup->id }}))"
                            class="absolute top-2 right-2 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-black/10 hover:bg-black/20 text-gray-600 hover:text-gray-900 transition-colors"
                            style="line-height: 1;">
                            <i class="fas fa-times text-sm"></i>
                        </button>
                    @endif

                    {{-- Content (server-rendered so Livewire components work) --}}
                    <div>{!! $popup->content !!}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('popupEngine', (popups) => ({
        allPopups: popups,
        visiblePopups: [],
        listeners: [],

        init() {
            this.allPopups.forEach(popup => {
                if (this.isDismissed(popup)) return;
                popup._visible = false;
                popup._dismissing = false;
                this.setupTrigger(popup);
            });
        },

        isDismissed(popup) {
            if (popup.frequency_days === 0) return false;
            const cookie = document.cookie.split('; ').find(c => c.startsWith(popup.cookie_name + '='));
            return !!cookie;
        },

        setDismissCookie(popup) {
            if (popup.frequency_days === 0) return;
            const maxAge = popup.frequency_days * 86400;
            document.cookie = popup.cookie_name + '=1; path=/; max-age=' + maxAge + '; SameSite=Lax';
        },

        setupTrigger(popup) {
            switch (popup.trigger_type) {
                case 'page_load':
                    setTimeout(() => this.showPopup(popup), 300);
                    break;

                case 'timed':
                    const delay = parseInt(popup.trigger_value) || 5;
                    setTimeout(() => this.showPopup(popup), delay * 1000);
                    break;

                case 'scroll':
                    const threshold = parseInt(popup.trigger_value) || 50;
                    const scrollHandler = () => {
                        const scrollPercent = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                        if (scrollPercent >= threshold) {
                            this.showPopup(popup);
                            window.removeEventListener('scroll', scrollHandler);
                        }
                    };
                    window.addEventListener('scroll', scrollHandler, { passive: true });
                    this.listeners.push({ type: 'scroll', handler: scrollHandler });
                    break;

                case 'exit_intent':
                    const exitHandler = (e) => {
                        if (e.clientY <= 0) {
                            this.showPopup(popup);
                            document.removeEventListener('mouseout', exitHandler);
                        }
                    };
                    document.addEventListener('mouseout', exitHandler);
                    this.listeners.push({ type: 'mouseout', handler: exitHandler });
                    break;

                case 'click':
                    if (popup.trigger_value) {
                        const clickHandler = () => this.showPopup(popup);
                        document.querySelectorAll(popup.trigger_value).forEach(el => {
                            el.addEventListener('click', clickHandler);
                        });
                    }
                    break;
            }
        },

        getPopup(id) {
            return this.allPopups.find(p => p.id === id) || {};
        },

        isVisible(id) {
            return !!this.visiblePopups.find(p => p.id === id);
        },

        isDismissing(id) {
            const popup = this.visiblePopups.find(p => p.id === id);
            return popup ? popup._dismissing : false;
        },

        showPopup(popup) {
            if (this.visiblePopups.find(p => p.id === popup.id)) return;
            popup._dismissing = false;
            this.visiblePopups.push(popup);
        },

        dismissPopup(popup) {
            if (!popup || !popup.id) return;
            popup._dismissing = true;
            this.setDismissCookie(popup);
            setTimeout(() => {
                this.visiblePopups = this.visiblePopups.filter(p => p.id !== popup.id);
            }, 300);
        },

        getAnimation(popup) {
            const animMap = {
                'fade_in':     { in: 'popupFadeIn',     out: 'popupFadeOut' },
                'slide_down':  { in: 'popupSlideDown',  out: 'popupSlideDownOut' },
                'slide_up':    { in: 'popupSlideUp',    out: 'popupSlideUpOut' },
                'slide_left':  { in: 'popupSlideLeft',  out: 'popupSlideLeftOut' },
                'slide_right': { in: 'popupSlideRight', out: 'popupSlideRightOut' },
                'zoom_in':     { in: 'popupZoomIn',     out: 'popupZoomInOut' },
                'none':        { in: 'none',            out: 'none' },
            };

            const anim = animMap[popup.animation] || animMap['fade_in'];
            const name = popup._dismissing ? anim.out : anim.in;

            if (name === 'none') return 'none';
            return name + ' 0.3s ease forwards';
        },

        getPositionClasses(popup) {
            const map = {
                'center': 'inset-0 flex items-center justify-center p-4',
                'top': 'top-0 left-0 right-0 flex justify-center p-4',
                'bottom': 'bottom-0 left-0 right-0 flex justify-center p-4',
                'left': 'inset-y-0 left-0 flex items-center p-4',
                'right': 'inset-y-0 right-0 flex items-center justify-end p-4',
                'full': 'inset-0 flex items-center justify-center',
            };
            return map[popup.position] || map['center'];
        },

        getPopupStyles(popup) {
            const shadowMap = {
                'none': 'none',
                'sm': '0 1px 2px rgba(0,0,0,0.05)',
                'md': '0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -2px rgba(0,0,0,0.1)',
                'lg': '0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -4px rgba(0,0,0,0.1)',
                'xl': '0 20px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1)',
            };

            let styles = [
                'background-color: ' + popup.bg_color,
                'border-radius: ' + popup.border_radius + 'px',
                'padding: ' + popup.padding + 'px',
                'box-shadow: ' + (shadowMap[popup.shadow] || shadowMap['lg']),
                'max-width: ' + popup.max_width_vw + 'vw',
                'overflow: hidden',
            ];

            if (popup.position !== 'full') {
                styles.push('width: ' + popup.width_px + 'px');
            } else {
                styles.push('width: 100%');
                styles.push('height: 100%');
            }

            if (popup.border_width > 0) {
                styles.push('border: ' + popup.border_width + 'px solid ' + popup.border_color);
            }

            return styles.join('; ');
        },

        destroy() {
            this.listeners.forEach(({ type, handler }) => {
                if (type === 'scroll') window.removeEventListener(type, handler);
                if (type === 'mouseout') document.removeEventListener(type, handler);
            });
        }
    }));
});
</script>

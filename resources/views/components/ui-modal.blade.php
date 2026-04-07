{{-- Global Tailwind modal that replaces native confirm/alert/prompt.
     Exposes a JS API on window.tcModal:
       tcModal.confirm(message, opts) -> Promise<boolean>
       tcModal.alert(message, opts)   -> Promise<void>
       tcModal.prompt(message, opts)  -> Promise<string|null>

     opts: { title, confirmText, cancelText, variant: 'default'|'danger'|'warning', defaultValue }
--}}
<div
    x-data="tcModalComponent()"
    x-show="open"
    x-cloak
    @keydown.escape.window="cancel()"
    class="fixed inset-0 z-[9999] flex items-center justify-center"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="open"
        x-transition:enter="transition-opacity ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="absolute inset-0 bg-gray-900/70 backdrop-blur-sm"
        @click="cancel()"
    ></div>

    {{-- Dialog --}}
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95 translate-y-2"
        x-transition:enter-end="opacity-100 scale-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative w-full max-w-md mx-4 bg-white dark:bg-gray-800 rounded-xl shadow-2xl ring-1 ring-black/5 dark:ring-white/10 overflow-hidden"
    >
        <div class="p-6">
            <div class="flex items-start gap-4">
                {{-- Icon --}}
                <div
                    class="flex-shrink-0 flex items-center justify-center h-10 w-10 rounded-full"
                    :class="{
                        'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400': variant === 'danger',
                        'bg-yellow-100 dark:bg-yellow-900/30 text-yellow-600 dark:text-yellow-400': variant === 'warning',
                        'bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400': variant === 'default',
                    }"
                >
                    <template x-if="variant === 'danger'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                    </template>
                    <template x-if="variant === 'warning'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                    </template>
                    <template x-if="variant === 'default'">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
                    </template>
                </div>

                <div class="flex-1 min-w-0">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white" x-text="title"></h3>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-300 whitespace-pre-line" x-text="message"></p>

                    {{-- Prompt input --}}
                    <template x-if="mode === 'prompt'">
                        <input
                            x-ref="promptInput"
                            type="text"
                            x-model="inputValue"
                            @keydown.enter.prevent="confirm()"
                            class="mt-3 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                    </template>
                </div>
            </div>
        </div>

        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 flex justify-end gap-2">
            <template x-if="mode !== 'alert'">
                <button
                    type="button"
                    @click="cancel()"
                    class="inline-flex justify-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    x-text="cancelText"
                ></button>
            </template>
            <button
                type="button"
                x-ref="confirmBtn"
                @click="confirm()"
                class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white border border-transparent rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-offset-2"
                :class="{
                    'bg-red-600 hover:bg-red-700 focus:ring-red-500': variant === 'danger',
                    'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500': variant === 'warning',
                    'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500': variant === 'default',
                }"
                x-text="confirmText"
            ></button>
        </div>
    </div>
</div>

<script>
    // Define window.tcModal IMMEDIATELY (before Alpine evaluates any handlers).
    // It queues calls until the Alpine component instance attaches itself,
    // then proxies through to the live instance.
    (function () {
        if (window.tcModal && window.tcModal.__initialized) return;
        var queue = [];
        var instance = null;
        function call(method, message, opts) {
            if (instance) return instance._show(method, message, opts);
            return new Promise(function (resolve) {
                queue.push({ method: method, message: message, opts: opts, resolve: resolve });
            });
        }
        window.tcModal = {
            confirm: function (message, opts) { return call('confirm', message, opts || {}); },
            alert:   function (message, opts) { return call('alert',   message, opts || {}); },
            prompt:  function (message, opts) { return call('prompt',  message, opts || {}); },
            _attach: function (inst) {
                instance = inst;
                this.__initialized = true;
                while (queue.length) {
                    var q = queue.shift();
                    inst._show(q.method, q.message, q.opts).then(q.resolve);
                }
            },
        };
    })();

    function tcModalComponent() {
        return {
            open: false,
            mode: 'confirm', // 'confirm' | 'alert' | 'prompt'
            title: 'Confirm',
            message: '',
            confirmText: 'OK',
            cancelText: 'Cancel',
            variant: 'default',
            inputValue: '',
            _resolve: null,

            init() {
                window.tcModal._attach(this);
            },

            _show(mode, message, opts) {
                this.mode = mode;
                this.message = message ?? '';
                this.title = opts.title ?? (mode === 'alert' ? 'Notice' : mode === 'prompt' ? 'Input required' : 'Confirm');
                this.confirmText = opts.confirmText ?? (mode === 'alert' ? 'OK' : mode === 'prompt' ? 'OK' : 'Confirm');
                this.cancelText = opts.cancelText ?? 'Cancel';
                this.variant = opts.variant ?? (mode === 'confirm' ? 'warning' : 'default');
                this.inputValue = opts.defaultValue ?? '';
                this.open = true;

                this.$nextTick(() => {
                    if (mode === 'prompt' && this.$refs.promptInput) {
                        this.$refs.promptInput.focus();
                        this.$refs.promptInput.select();
                    } else if (this.$refs.confirmBtn) {
                        this.$refs.confirmBtn.focus();
                    }
                });

                return new Promise((resolve) => {
                    this._resolve = resolve;
                });
            },

            confirm() {
                this.open = false;
                if (this._resolve) {
                    if (this.mode === 'prompt') this._resolve(this.inputValue);
                    else if (this.mode === 'alert') this._resolve();
                    else this._resolve(true);
                    this._resolve = null;
                }
            },

            cancel() {
                this.open = false;
                if (this._resolve) {
                    if (this.mode === 'prompt') this._resolve(null);
                    else if (this.mode === 'alert') this._resolve();
                    else this._resolve(false);
                    this._resolve = null;
                }
            },
        };
    }
</script>

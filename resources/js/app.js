import './bootstrap';
import './creator-qr-card';
import './creator-feed-masonry';

/**
 * Auto-inject a spinner into every submit button and toggle it on form submit.
 * Works with both regular forms and Livewire (Alpine x-on:submit) forms.
 */
function injectSpinners(root = document) {
    root.querySelectorAll('button[type="submit"]:not([data-spinner-ready])').forEach(btn => {
        btn.setAttribute('data-spinner-ready', '');

        // Inject spinner element as the first child
        const spinner = document.createElement('span');
        spinner.className = 'btn-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        btn.insertBefore(spinner, btn.firstChild);
    });
}

function showSpinner(btn) {
    btn.classList.add('btn-loading');
}

function hideSpinner(btn) {
    btn.classList.remove('btn-loading');
}

// Initial injection
document.addEventListener('DOMContentLoaded', () => injectSpinners());

// Re-inject after Livewire DOM updates
document.addEventListener('livewire:navigated', () => injectSpinners());

// Observe dynamic DOM changes (Livewire morphs, Alpine x-if, etc.)
new MutationObserver(() => injectSpinners()).observe(document.body, {
    childList: true,
    subtree: true,
});

// Regular form submissions
document.addEventListener('submit', (e) => {
    const btn = e.target.querySelector('button[type="submit"]');
    if (btn) showSpinner(btn);
});

// Livewire request lifecycle
document.addEventListener('livewire:init', () => {
    Livewire.hook('request', ({ respond, fail }) => {
        // Show spinners on all submit buttons inside the requesting component
        document.querySelectorAll('button[type="submit"].btn-loading').forEach(hideSpinner);

        respond(() => {
            document.querySelectorAll('button[type="submit"].btn-loading').forEach(hideSpinner);
        });

        fail(() => {
            document.querySelectorAll('button[type="submit"].btn-loading').forEach(hideSpinner);
        });
    });
});

// Alpine-intercepted forms (x-on:submit.prevent) — the native 'submit' event
// never fires, so we catch the click on the submit button instead.
document.addEventListener('click', (e) => {
    const btn = e.target.closest('button[type="submit"]');
    if (!btn) return;

    const form = btn.closest('form');
    if (form && form.hasAttribute('x-on:submit.prevent')) {
        showSpinner(btn);
    }
});

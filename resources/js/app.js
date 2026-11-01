// Public site JS entry.
//
// Livewire 4 ships and starts Alpine itself (auto-injected assets), and the
// site layout always mounts a Livewire component (the cart drawer), so Alpine
// is present on every public page. We must NOT import/start a second Alpine
// here — a duplicate instance has no `$wire` magic, so it throws
// "$wire is not defined" on `x-show="$wire.open"` and makes Livewire morphs
// (e.g. the cart drawer stepper) flaky. Rely on Livewire's global Alpine.
import { initMotion } from './site/motion.js';

// Preloader, page curtain, custom cursor, scroll reveal + responsive compact
// flag. All disabled under prefers-reduced-motion / when JS is off.
initMotion();

// Toast stack — same shape as admin.js so Livewire components dispatch once
// and both frontends handle the same payload: { tone, title, message }.
document.addEventListener('alpine:init', () => {
    window.Alpine.store('toasts', {
        items: [],
        seq: 0,
        push({ tone = 'info', title = '', message = '', timeout = 4500 } = {}) {
            const id = ++this.seq;
            this.items.push({ id, tone, title, message });
            if (timeout) {
                setTimeout(() => this.dismiss(id), timeout);
            }
        },
        dismiss(id) {
            this.items = this.items.filter((t) => t.id !== id);
        },
    });
});

document.addEventListener('livewire:init', () => {
    window.Livewire.on('toast', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        window.Alpine.store('toasts').push(data || {});
    });
});

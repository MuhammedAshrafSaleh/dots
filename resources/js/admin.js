// Admin JS entry.
//
// Livewire 4 ships and starts Alpine itself (auto-injected assets), so we must
// NOT import/start a second Alpine here. We only register the admin widget
// helpers against the Alpine instance Livewire provides, and wire Livewire
// events (toasts) into an Alpine store.

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Toast stack. Livewire components raise them with
    // $this->dispatch('toast', tone, title, message).
    Alpine.store('toasts', {
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

    // Drag-to-reorder. Mirrors the design's useReorder (HTML5 DnD). Emits the
    // new order of item keys to a Livewire method via `wire` + `method`.
    Alpine.data('sortable', ({ wire = null, method = 'reorder' } = {}) => ({
        dragIndex: null,
        start(e, i) {
            this.dragIndex = i;
            e.dataTransfer.effectAllowed = 'move';
        },
        over(e) {
            e.preventDefault();
        },
        drop(e, i) {
            e.preventDefault();
            const from = this.dragIndex;
            this.dragIndex = null;
            if (from === null || from === i) {
                return;
            }
            const nodes = Array.from(this.$el.querySelectorAll('[data-sortable-key]'));
            const keys = nodes.map((n) => n.dataset.sortableKey);
            const [moved] = keys.splice(from, 1);
            keys.splice(i, 0, moved);
            if (wire) {
                wire[method](keys);
            } else {
                this.$dispatch('sorted', keys);
            }
        },
    }));
});

document.addEventListener('livewire:init', () => {
    window.Livewire.on('toast', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        window.Alpine.store('toasts').push(data || {});
    });
});

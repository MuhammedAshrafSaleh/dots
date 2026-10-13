// Admin JS entry.
//
// Livewire 4 ships and starts Alpine itself (auto-injected assets), so we must
// NOT import/start a second Alpine here. We only register the admin widget
// helpers against the Alpine instance Livewire provides, and wire Livewire
// events (toasts) into an Alpine store.

import { Editor, Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';

// Custom block node: <div class="d-callout">…</div>
const Callout = Node.create({
    name: 'callout',
    group: 'block',
    content: 'inline*',
    parseHTML() {
        return [{ tag: 'div.d-callout' }];
    },
    renderHTML({ HTMLAttributes }) {
        return ['div', mergeAttributes(HTMLAttributes, { class: 'd-callout' }), 0];
    },
    addCommands() {
        return {
            setCallout: () => ({ commands }) => commands.setNode('callout'),
        };
    },
});

const EXTENSIONS = [
    StarterKit.configure({
        heading: { levels: [2, 3] },
        horizontalRule: false,
        code: false,
        codeBlock: false,
    }),
    Link.configure({ openOnClick: false }),
    Image,
    Callout,
];

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
            // Only this container's direct items, so nested sortables (the
            // category tree) don't leak child keys into a parent's order.
            const nodes = Array.from(this.$el.querySelectorAll(':scope > [data-sortable-key]'));
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

    // TipTap rich-text editor. Mounts two editors (EN + AR) inside wire:ignore
    // so Livewire never diffs the ProseMirror DOM. The `en` and `ar` params are
    // $wire.entangle() proxies — changes flow both ways automatically.
    Alpine.data('richEditor', ({ en = null, ar = null, minH = 220 } = {}) => ({
        lang: 'ar',
        en,
        ar,
        _editors: {},
        _tick: 0, // incremented on TipTap selection/transaction; makes active() reactive

        init() {
            this._mkEditor('en', this.$refs.en, minH);
            this._mkEditor('ar', this.$refs.ar, minH);

            // Sync TipTap when Livewire pushes a new value (e.g. after load).
            this.$watch('en', (v) => {
                const ed = this._editors.en;
                if (ed && !ed.isFocused && ed.getHTML() !== v) {
                    ed.commands.setContent(v || '', false);
                }
            });
            this.$watch('ar', (v) => {
                const ed = this._editors.ar;
                if (ed && !ed.isFocused && ed.getHTML() !== v) {
                    ed.commands.setContent(v || '', false);
                }
            });

        },

        destroy() {
            Object.values(this._editors).forEach((e) => e.destroy());
        },

        _mkEditor(locale, element, minH) {
            const self = this;
            this._editors[locale] = new Editor({
                element,
                extensions: EXTENSIONS,
                content: (locale === 'en' ? this.en : this.ar) || '',
                editorProps: {
                    attributes: {
                        class: 'd-prose',
                        dir: locale === 'ar' ? 'rtl' : 'ltr',
                        lang: locale,
                        style: `min-height:${minH}px;padding:14px 16px;outline:none;font-size:15px;`,
                    },
                },
                onUpdate({ editor }) {
                    self[locale] = editor.getHTML();
                },
                onSelectionUpdate() {
                    self._tick++;
                },
            });
        },

        // Run a toolbar action on the currently visible editor.
        cmd(action) {
            const ed = this._editors[this.lang];
            if (!ed) return;
            const c = ed.chain().focus();
            if (action === 'h2')        c.toggleHeading({ level: 2 }).run();
            else if (action === 'h3')   c.toggleHeading({ level: 3 }).run();
            else if (action === 'p')    c.setParagraph().run();
            else if (action === 'bold') c.toggleBold().run();
            else if (action === 'italic') c.toggleItalic().run();
            else if (action === 'link') {
                const prev = ed.getAttributes('link').href;
                const url  = window.prompt('URL', prev || 'https://');
                if (url === null) return;
                if (url === '') { c.unsetLink().run(); return; }
                c.setLink({ href: url, target: '_blank' }).run();
            }
            else if (action === 'ul')       c.toggleBulletList().run();
            else if (action === 'ol')       c.toggleOrderedList().run();
            else if (action === 'bq')       c.toggleBlockquote().run();
            else if (action === 'image') {
                const src = window.prompt('Image URL');
                if (src) c.setImage({ src }).run();
            }
            else if (action === 'callout')  c.setCallout().run();
        },

        // True when the active editor has the given node/mark active.
        // Accessing `_tick` registers it as a reactive dependency so Alpine
        // re-evaluates this when TipTap's selection changes.
        active(action) {
            void this._tick;
            const ed = this._editors[this.lang];
            if (!ed) return false;
            if (action === 'h2')      return ed.isActive('heading', { level: 2 });
            if (action === 'h3')      return ed.isActive('heading', { level: 3 });
            if (action === 'p')       return ed.isActive('paragraph');
            if (action === 'ul')      return ed.isActive('bulletList');
            if (action === 'ol')      return ed.isActive('orderedList');
            if (action === 'bq')      return ed.isActive('blockquote');
            if (action === 'link')    return ed.isActive('link');
            if (action === 'callout') return ed.isActive('callout');
            return ed.isActive(action);
        },
    }));
});

document.addEventListener('livewire:init', () => {
    window.Livewire.on('toast', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        window.Alpine.store('toasts').push(data || {});
    });
});

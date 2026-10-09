@props([
    'label' => null,
    'model' => null,     // base wire:model path → model.en / model.ar
    'minH' => 220,
])
@php
    // Mirror of admin-kit.jsx ARich. One language at a time (AR/EN), toolbar:
    // headings, bold, italic, link, lists, quote, image, callout. Content is
    // sanitised server-side on save (App\Support\Html::clean → dots_rich).
    $tools = [
        ['heading-2', 'formatBlock', 'h2'], ['heading-3', 'formatBlock', 'h3'], ['pilcrow', 'formatBlock', 'p'], '|',
        ['bold', 'bold', null], ['italic', 'italic', null], ['link', 'createLink', null], '|',
        ['list', 'insertUnorderedList', null], ['list-ordered', 'insertOrderedList', null], ['text-quote', 'formatBlock', 'blockquote'], '|',
        ['image', 'img', null], ['info', 'callout', null],
    ];
@endphp
<div class="d-field"
     x-data="{
        lang: @js(app()->getLocale()),
        model: { en: $wire.entangle('{{ $model }}.en'), ar: $wire.entangle('{{ $model }}.ar') },
        init() {
            this.$refs.en.innerHTML = this.model.en || '';
            this.$refs.ar.innerHTML = this.model.ar || '';
        },
        sync(l) { this.model[l] = this.$refs[l].innerHTML; },
        run(cmd, arg, l) {
            const el = this.$refs[l]; el.focus();
            if (cmd === 'img') { document.execCommand('insertHTML', false, '<figure><img src=\'\' alt=\'\' style=\'width:100%\'/><figcaption>' + (l === 'ar' ? 'تعليق الصورة' : 'Image caption') + '</figcaption></figure><p></p>'); }
            else if (cmd === 'callout') { document.execCommand('insertHTML', false, '<div class=\'d-callout\'>' + (l === 'ar' ? 'نص التنبيه' : 'Callout text') + '</div><p></p>'); }
            else if (cmd === 'createLink') { const u = prompt('URL', 'https://'); if (u) document.execCommand('createLink', false, u); }
            else { document.execCommand(cmd, false, arg); }
            this.sync(l);
        },
     }">
    @if ($label)
        <span class="d-label" style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
            {{ $label }}
            <span class="d-tabs d-tabs--pill" style="flex: none;">
                <button type="button" class="d-tab" :aria-selected="lang === 'ar'" @click="lang = 'ar'">{{ __('admin.widgets.arabic') }}</button>
                <button type="button" class="d-tab" :aria-selected="lang === 'en'" @click="lang = 'en'">English</button>
            </span>
        </span>
    @endif

    <div style="border: 1px solid var(--border-control); border-radius: var(--radius-md); overflow: hidden; background: var(--white);">
        <div dir="ltr" style="display: flex; flex-wrap: wrap; gap: 2px; padding: 6px; border-bottom: 1px solid var(--border-1); background: var(--ink-50);">
            @foreach ($tools as $t)
                @if ($t === '|')
                    <span style="width: 1px; background: var(--border-1); margin: 4px;"></span>
                @else
                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm"
                            @mousedown.prevent
                            @click="run('{{ $t[1] }}', {{ $t[2] === null ? 'null' : "'".$t[2]."'" }}, lang)"
                            aria-label="{{ $t[0] }}">
                        <x-icon :name="$t[0]" size="16" />
                    </button>
                @endif
            @endforeach
        </div>
        @foreach (['en', 'ar'] as $loc)
            <div x-ref="{{ $loc }}" contenteditable x-show="lang === '{{ $loc }}'"
                 @input="sync('{{ $loc }}')" @blur="sync('{{ $loc }}')"
                 dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}" lang="{{ $loc }}"
                 class="d-prose"
                 style="min-height: {{ $minH }}px; padding: 14px 16px; outline: none; font-size: 15px;"></div>
        @endforeach
    </div>
</div>

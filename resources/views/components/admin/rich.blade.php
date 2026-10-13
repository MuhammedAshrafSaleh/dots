@props([
    'label' => null,
    'model' => null,     // base wire:model path → model.en / model.ar
    'minH'  => 220,
])
{{--
    TipTap bilingual rich-text editor (AR / EN). Two ProseMirror instances share
    a single toolbar; wire:ignore prevents Livewire from diffing their DOM.
    Content flows via $wire.entangle() ↔ Alpine reactive properties.
    Server-side: call Html::cleanTranslations($this->model) before saving.
--}}
@php
    $tools = [
        ['h2','heading-2'],['h3','heading-3'],['p','pilcrow'],'|',
        ['bold','bold'],['italic','italic'],['link','link'],'|',
        ['ul','list'],['ol','list-ordered'],['bq','text-quote'],'|',
        ['image','image'],['callout','info'],
    ];
@endphp
<div class="d-field"
     x-data="richEditor({
         en: $wire.entangle('{{ $model }}.en'),
         ar: $wire.entangle('{{ $model }}.ar'),
         minH: {{ $minH }}
     })">

    @if ($label)
        <span class="d-label" style="display:flex;align-items:center;justify-content:space-between;gap:8px;flex-wrap:wrap;">
            {{ $label }}
            <span class="d-tabs d-tabs--pill" style="flex:none;">
                <button type="button" class="d-tab" :aria-selected="lang === 'ar'" @click="lang = 'ar'">{{ __('admin.widgets.arabic') }}</button>
                <button type="button" class="d-tab" :aria-selected="lang === 'en'" @click="lang = 'en'">English</button>
            </span>
        </span>
    @endif

    <div style="border:1px solid var(--border-control);border-radius:var(--radius-md);overflow:hidden;background:var(--white);">

        {{-- Toolbar (outside wire:ignore — Alpine reactivity still works) --}}
        <div dir="ltr" style="display:flex;flex-wrap:wrap;gap:2px;padding:6px;border-bottom:1px solid var(--border-1);background:var(--ink-50);">
            @foreach ($tools as $t)
                @if ($t === '|')
                    <span style="width:1px;background:var(--border-1);margin:4px;"></span>
                @else
                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm"
                            :class="{ 'is-active': active('{{ $t[0] }}') }"
                            @mousedown.prevent
                            @click="cmd('{{ $t[0] }}')"
                            aria-label="{{ $t[1] }}">
                        <x-icon :name="$t[1]" size="16" />
                    </button>
                @endif
            @endforeach
        </div>

        {{-- TipTap mounts here; Livewire must not diff these nodes --}}
        <div wire:ignore>
            <div x-ref="en" x-show="lang === 'en'"></div>
            <div x-ref="ar" x-show="lang === 'ar'"></div>
        </div>
    </div>
</div>

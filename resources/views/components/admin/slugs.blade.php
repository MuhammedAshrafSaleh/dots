@props([
    'model' => 'slug',       // base wire:model path → slug.en / slug.ar
    'path' => '',            // URL path segment shown in the preview, e.g. "/products"
    'generate' => null,      // optional Livewire method name for the auto-generate button
])
@php
    // Mirror of admin-kit.jsx ASlugs (per-locale slug inputs), with an
    // auto-generate button (phase requirement) that defers to the parent.
    $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'dots.eg';
@endphp
<div class="d-field">
    <span class="d-label" style="display: flex; justify-content: space-between; gap: 8px;">
        {{ __('admin.widgets.slug') }}
        @if ($generate)
            <button type="button" class="a-link" wire:click="{{ $generate }}" style="font-size: 12px;">
                <x-icon name="refresh-cw" size="13" /> {{ __('admin.widgets.auto_generate') }}
            </button>
        @endif
    </span>
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
        @foreach (['en' => 'EN', 'ar' => __('admin.widgets.ar')] as $loc => $tag)
            <div x-data="{ v: @js('') }" x-init="v = $refs.s{{ $loc }}.value">
                <x-input
                    dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}"
                    lang="{{ $loc }}"
                    wire:model="{{ $model }}.{{ $loc }}"
                    x-ref="s{{ $loc }}"
                    @input="v = $event.target.value"
                >
                    <x-slot:label>{{ __('admin.widgets.slug') }} <span class="d-label__opt">· {{ $tag }}</span></x-slot:label>
                    <x-slot:help><span dir="ltr">{{ $host }}/{{ $loc }}{{ $path }}/<span x-text="v"></span></span></x-slot:help>
                </x-input>
            </div>
        @endforeach
    </div>
</div>

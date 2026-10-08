@props([
    'label' => '',
    'model' => null,       // base wire:model path, e.g. "name" → name.en / name.ar
    'multiline' => false,
    'rows' => 3,
    'counter' => null,     // max length to show a live counter against
])
{{-- Mirror of admin-kit.jsx ABi: paired EN/AR inputs for a translatable field. --}}
<div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;" class="a-bi">
    @foreach (['en' => 'EN', 'ar' => __('admin.widgets.ar')] as $loc => $tag)
        <div x-data="{ len: 0 }" x-init="len = ($refs.inp?.value ?? '').length">
            <x-input
                :multiline="$multiline"
                :rows="$multiline ? $rows : null"
                dir="{{ $loc === 'ar' ? 'rtl' : 'ltr' }}"
                lang="{{ $loc }}"
                wire:model="{{ $model }}.{{ $loc }}"
                x-ref="inp"
                x-on:input="len = $event.target.value.length"
            >
                <x-slot:label>
                    <span style="display: flex; justify-content: space-between; gap: 8px;">
                        <span>{{ $label }} <span class="d-label__opt">· {{ $tag }}</span></span>
                        @if ($counter)
                            <span class="a-count" x-bind:class="{ 'is-over': len > {{ $counter }} }"><span x-text="len"></span>/{{ $counter }}</span>
                        @endif
                    </span>
                </x-slot:label>
            </x-input>
        </div>
    @endforeach
</div>

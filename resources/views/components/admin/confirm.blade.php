@props([
    'title' => null,
    'body' => null,
    'confirmLabel' => null,
    'tone' => 'danger',        // danger | primary
    'show' => 'confirm',       // Alpine boolean expression controlling visibility
])
{{-- Mirror of a styled confirm dialog. Lives inside an Alpine scope exposing
     `$show`. Put the confirm button (with its wire:click) in the $action slot
     so the caller owns the action; it should set `$show = false` after firing. --}}
<template x-if="{{ $show }}">
    <div>
        <div class="d-overlay" @click="{{ $show }} = false"></div>
        <div role="alertdialog" aria-modal="true"
             @keydown.escape.window="{{ $show }} = false"
             style="position: fixed; inset: 0; display: grid; place-items: center; padding: 24px; z-index: 60;">
            <div class="a-card" style="width: 100%; max-width: 420px; padding: 24px; display: grid; gap: 16px;" @click.stop>
                <div style="display: grid; gap: 6px;">
                    <h3 style="margin: 0; font: 700 18px var(--font-body);">{{ $title }}</h3>
                    @if ($body)<p style="margin: 0; font: 500 14px/1.5 var(--font-body); color: var(--fg-2);">{{ $body }}</p>@endif
                </div>
                <div style="display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">
                    <x-button variant="ghost" @click="{{ $show }} = false">{{ __('admin.widgets.cancel') }}</x-button>
                    {{ $action ?? '' }}
                </div>
            </div>
        </div>
    </div>
</template>

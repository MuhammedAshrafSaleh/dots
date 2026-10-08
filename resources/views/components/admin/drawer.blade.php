@props([
    'title' => null,
    'sub' => null,
    'width' => 480,
    'show' => 'drawer',   // Alpine boolean expression controlling visibility
])
{{-- Mirror of admin-kit.jsx ADrawer: right-hand slide-over. Lives inside an
     Alpine scope exposing `$show`. $foot slot holds the action buttons. --}}
<template x-if="{{ $show }}">
    <div>
        <div class="d-overlay" @click="{{ $show }} = false"></div>
        <aside class="d-drawer" role="dialog" aria-modal="true" style="width: {{ $width }}px;"
               @keydown.escape.window="{{ $show }} = false">
            <div class="d-drawer__head">
                <div style="min-width: 0; flex: 1;">
                    <h3>{{ $title }}</h3>
                    @if ($sub)<div class="a-sub" style="margin-top: 6px;">{{ $sub }}</div>@endif
                </div>
                <button type="button" class="d-btn d-btn--ghost d-btn--icon" @click="{{ $show }} = false" aria-label="{{ __('admin.widgets.close') }}">
                    <x-icon name="x" />
                </button>
            </div>
            <div class="d-drawer__body" style="padding: 20px; display: grid; gap: 18px; align-content: start;">
                {{ $slot }}
            </div>
            @isset($foot)
                <div class="d-drawer__foot" style="display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap;">{{ $foot }}</div>
            @endisset
        </aside>
    </div>
</template>

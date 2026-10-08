@props([
    'icon' => 'search-x',
    'title' => null,
    'body' => null,
])
{{-- Mirror of admin-kit.jsx AEmpty. $actions slot holds a button. --}}
<div style="display: grid; justify-items: center; text-align: center; gap: 10px; padding: 56px 24px;">
    <span style="width: 56px; height: 56px; border-radius: 50%; background: var(--yellow-100); display: grid; place-items: center;">
        <x-icon :name="$icon" size="26" />
    </span>
    <b style="font: 800 16px/1.3 var(--font-body);">{{ $title }}</b>
    @if ($body)<span style="font: 500 14px/1.5 var(--font-body); color: var(--fg-2); max-width: 380px;">{{ $body }}</span>@endif
    {{ $actions ?? '' }}
</div>

@props([
    'tone' => 'info',
    'title' => null,
])
@php
    // Mirror of admin-kit.jsx AAlert.
    $icon = [
        'error' => 'circle-alert',
        'warning' => 'triangle-alert',
        'info' => 'info',
        'success' => 'circle-check',
    ][$tone] ?? 'info';
@endphp
<div {{ $attributes->class(['a-alert', "a-alert--{$tone}"]) }} role="{{ $tone === 'error' ? 'alert' : 'status' }}">
    <x-icon :name="$icon" size="20" />
    <div>
        @if ($title)<b>{{ $title }}</b>@endif
        {{ $slot }}
    </div>
    <span></span>
</div>

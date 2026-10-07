@props([
    'tone' => 'neutral',
    'dot' => false,
])
{{-- Mirror of design/website/src/components/badges/Badge.jsx. --}}
<span {{ $attributes->class(['d-badge', "d-badge--{$tone}"]) }}>
    @if ($dot)<span class="d-badge__dot"></span>@endif{{ $slot }}
</span>

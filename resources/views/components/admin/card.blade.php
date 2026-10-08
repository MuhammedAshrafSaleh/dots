@props([
    'title' => null,
    'icon' => null,
    'body' => true,
])
{{-- Mirror of admin-kit.jsx ACard. Slots: default (content), $action (header), $foot. --}}
<section {{ $attributes->class(['a-card']) }}>
    @if ($title || isset($action))
        <header class="a-card__head">
            @if ($title)<h3>@if ($icon)<x-icon :name="$icon" size="18" />@endif{{ $title }}</h3>@endif
            {{ $action ?? '' }}
        </header>
    @endif
    @if ($body)
        <div class="a-card__body">{{ $slot }}</div>
    @else
        {{ $slot }}
    @endif
    @isset($foot)<footer class="a-card__foot">{{ $foot }}</footer>@endisset
</section>

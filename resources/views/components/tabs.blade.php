@props([
    'tabs' => [],
    'value' => null,
    'variant' => 'line',
])
@php
    // Mirror of design/website/src/components/wayfinding/Tabs.jsx.
    // $tabs: list of ['id','label','count'?,'disabled'?]. Active state is self-contained;
    // listen for the `tab-changed` window event (detail = id) to react elsewhere.
    $active = $value ?? ($tabs[0]['id'] ?? null);
    $classes = array_filter(['d-tabs', $variant === 'pill' ? 'd-tabs--pill' : null]);
@endphp
<div role="tablist" x-data="{ active: @js($active) }" {{ $attributes->class($classes) }}>
    @foreach ($tabs as $tb)
        <button
            role="tab"
            type="button"
            class="d-tab"
            :aria-selected="active === @js($tb['id'])"
            @disabled($tb['disabled'] ?? false)
            @click="active = @js($tb['id']); $dispatch('tab-changed', @js($tb['id']))"
        >
            {{ $tb['label'] }}@if (isset($tb['count']))<span class="d-tab__n">{{ $tb['count'] }}</span>@endif
        </button>
    @endforeach
</div>

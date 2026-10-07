@props([
    'tone' => 'success',
    'title' => null,
    'message' => null,
    'actions' => [],
    'dismissible' => true,
])
@php
    // Mirror of design/website/src/components/feedback/Toast.jsx.
    // Place inside a `.d-toasts` container. tone: success | error | warning | info | cart.
    $icon = ['success' => 'check', 'error' => 'circle-alert', 'warning' => 'triangle-alert', 'info' => 'info', 'cart' => 'shopping-bag'][$tone] ?? 'info';
@endphp
<div
    x-data="{ show: true }"
    x-show="show"
    x-cloak
    {{ $attributes->class(['d-toast', "d-toast--{$tone}"]) }}
    role="{{ $tone === 'error' ? 'alert' : 'status' }}"
>
    <span class="d-toast__ico"><x-icon :name="$icon" size="16" stroke="2" /></span>
    <div style="min-width:0;">
        @if ($title)<div class="d-toast__title">{{ $title }}</div>@endif
        @if ($message)<div class="d-toast__msg">{{ $message }}</div>@endif
        @if (! empty($actions))
            <div class="d-toast__act">
                @foreach ($actions as $a)
                    @if (! empty($a['url']))
                        <a href="{{ $a['url'] }}">{{ $a['label'] }}</a>
                    @else
                        <button type="button">{{ $a['label'] }}</button>
                    @endif
                @endforeach
            </div>
        @endif
    </div>
    @if ($dismissible)
        <button type="button" class="d-toast__x" @click="show = false" aria-label="{{ __('ui.dismiss') }}"><x-icon name="x" size="16" /></button>
    @endif
</div>

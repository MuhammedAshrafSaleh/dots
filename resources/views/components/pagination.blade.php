@props([
    'page' => 1,
    'total' => 1,
    'count' => null,
    'perPage' => null,
])
@php
    // Mirror of design/website/src/components/wayfinding/Pagination.jsx.
    $page = max(1, (int) $page);
    $total = max(1, (int) $total);

    // Page window with gap markers.
    if ($total <= 7) {
        $window = range(1, $total);
    } else {
        $nums = collect([1, $total, $page - 1, $page, $page + 1])
            ->filter(fn ($n) => $n >= 1 && $n <= $total)->unique()->sort()->values();
        $window = [];
        foreach ($nums as $i => $n) {
            if ($i > 0 && $n - $nums[$i - 1] > 1) {
                $window[] = '…'.$n;
            }
            $window[] = $n;
        }
    }

    $url = fn ($n) => request()->fullUrlWithQuery(['page' => $n]);
@endphp
<nav aria-label="{{ __('ui.pagination') }}" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;justify-content:space-between;">
    @if ($count !== null && $perPage)
        <span style="font:600 13px var(--font-body);color:var(--fg-2);">
            {{ __('ui.showing') }} {{ ($page - 1) * $perPage + 1 }}–{{ min($page * $perPage, $count) }} {{ __('ui.of') }} {{ number_format($count) }}
        </span>
    @endif
    <div class="d-pages">
        @if ($page <= 1)
            <span class="d-page" aria-disabled="true"><x-icon name="chevron-left" size="16" />{{ __('ui.prev') }}</span>
        @else
            <a class="d-page" href="{{ $url($page - 1) }}" aria-label="{{ __('ui.previous_page') }}"><x-icon name="chevron-left" size="16" />{{ __('ui.prev') }}</a>
        @endif

        @foreach ($window as $n)
            @if (is_string($n))
                <span class="d-page d-page--gap">…</span>
            @else
                <a class="d-page" href="{{ $url($n) }}" @if ($n === $page) aria-current="page" @endif>{{ $n }}</a>
            @endif
        @endforeach

        @if ($page >= $total)
            <span class="d-page" aria-disabled="true">{{ __('ui.next') }}<x-icon name="chevron-right" size="16" /></span>
        @else
            <a class="d-page" href="{{ $url($page + 1) }}" aria-label="{{ __('ui.next_page') }}">{{ __('ui.next') }}<x-icon name="chevron-right" size="16" /></a>
        @endif
    </div>
</nav>

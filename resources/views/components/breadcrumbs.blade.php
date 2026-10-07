@props([
    'items' => [],
])
{{-- Mirror of design/website/src/components/wayfinding/Breadcrumbs.jsx.
     $items: list of ['label','url'?]. Last item is the current page. Chevrons mirror in RTL. --}}
<nav aria-label="{{ __('ui.breadcrumb') }}">
    <ol class="d-crumbs">
        @foreach ($items as $i => $it)
            <li style="display:inline-flex;align-items:center;gap:6px;">
                @if ($i > 0)<x-icon name="chevron-right" size="14" />@endif
                @if ($loop->last || empty($it['url']))
                    <span aria-current="page">{{ $it['label'] }}</span>
                @else
                    <a href="{{ $it['url'] }}">{{ $it['label'] }}</a>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

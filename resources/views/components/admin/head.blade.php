@props([
    'title' => null,
    'sub' => null,
])
{{-- Mirror of admin-kit.jsx AHead. `$actions` slot holds buttons. --}}
<div style="display: grid; gap: 10px;">
    @isset($crumbs)<x-breadcrumbs :items="$crumbs" />@endisset
    <div class="a-head">
        <div style="min-width: 0;">
            <h1>{{ $title }}{{ $extra ?? '' }}</h1>
            @if ($sub)<p>{{ $sub }}</p>@endif
        </div>
        @isset($actions)<div class="a-head__act">{{ $actions }}</div>@endisset
    </div>
</div>

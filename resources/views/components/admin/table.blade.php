@props([
    'empty' => false,   // true when there are no rows; renders the $emptyState slot
])
{{-- Mirror of admin-kit.jsx ATable (desktop table; horizontal scroll on small
     screens via .a-scroll). Provide the header cells in $head and the <tr> rows
     in the default slot. When $empty, the $emptyState slot is shown instead. --}}
@if ($empty)
    {{ $emptyState ?? '' }}
@else
    <div class="a-scroll">
        <table class="a-table">
            <thead>
                <tr>{{ $head }}</tr>
            </thead>
            <tbody>
                {{ $slot }}
            </tbody>
        </table>
    </div>
@endif

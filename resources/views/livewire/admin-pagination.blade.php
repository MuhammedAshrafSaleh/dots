{{-- Livewire pagination in the design's .d-pages style (mirror of Pagination.jsx). --}}
@if ($paginator->hasPages())
    <nav aria-label="{{ __('ui.pagination') }}" style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; justify-content: space-between;">
        <span style="font: 600 13px var(--font-body); color: var(--fg-2);">
            {{ __('ui.showing') }} {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} {{ __('ui.of') }} {{ number_format($paginator->total()) }}
        </span>
        <div class="d-pages">
            @if ($paginator->onFirstPage())
                <span class="d-page" aria-disabled="true"><x-icon name="chevron-left" size="16" />{{ __('ui.prev') }}</span>
            @else
                <button type="button" class="d-page" wire:click="previousPage" aria-label="{{ __('ui.previous_page') }}"><x-icon name="chevron-left" size="16" />{{ __('ui.prev') }}</button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="d-page d-page--gap">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <button type="button" class="d-page" wire:click="gotoPage({{ $page }})" @if ($page == $paginator->currentPage()) aria-current="page" @endif>{{ $page }}</button>
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" class="d-page" wire:click="nextPage" aria-label="{{ __('ui.next_page') }}">{{ __('ui.next') }}<x-icon name="chevron-right" size="16" /></button>
            @else
                <span class="d-page" aria-disabled="true">{{ __('ui.next') }}<x-icon name="chevron-right" size="16" /></span>
            @endif
        </div>
    </nav>
@endif

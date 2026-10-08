<div
    class="a-search"
    style="position: relative; flex: 1; min-width: 0; max-width: 460px;"
    @click.outside="$wire.open = false"
    @keydown.escape="$wire.open = false"
>
    <form role="search" class="d-search d-search--sm" wire:submit.prevent>
        <label class="d-search__field">
            <x-icon name="search" size="18" />
            <input
                type="search"
                wire:model.live.debounce.350ms="q"
                @focus="if ($wire.q.trim() !== '') $wire.open = true"
                placeholder="{{ __('admin.shell.search_placeholder') }}"
                aria-label="{{ __('admin.shell.search') }}"
            >
        </label>
        <button type="submit" class="d-search__btn" aria-label="{{ __('admin.shell.search') }}" tabindex="-1">
            <x-icon name="arrow-right" size="18" />
        </button>
    </form>

    @if ($open)
        <div class="a-menu" style="position: absolute; inset-inline-start: 0; inset-inline-end: 0; top: calc(100% + 6px); max-height: 70vh; overflow: auto; padding: 6px;">
            @if ($results['total'] === 0)
                <div style="padding: 16px; text-align: center; color: var(--fg-3); font: 500 13px var(--font-body);">
                    {{ __('admin.shell.search_hint') }}
                </div>
            @else
                @foreach (['orders' => 'results_orders', 'products' => 'results_products', 'customers' => 'results_customers'] as $bucket => $labelKey)
                    @if (count($results[$bucket]))
                        <div class="a-side__group" style="padding: 6px 10px;">{{ __('admin.shell.'.$labelKey) }}</div>
                        @foreach ($results[$bucket] as $r)
                            @if ($r['url'])
                                <a href="{{ $r['url'] }}" wire:navigate>
                                    <span style="min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $r['label'] }}</span>
                                    @if ($r['meta'])<span class="a-mono a-sub" style="margin: 0;">{{ $r['meta'] }}</span>@endif
                                </a>
                            @else
                                <span style="display: flex; gap: 8px; align-items: center; padding: 8px 10px; color: var(--fg-2); font: 500 13px var(--font-body);">
                                    <span style="min-width: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $r['label'] }}</span>
                                    @if ($r['meta'])<span class="a-mono a-sub" style="margin: 0;">{{ $r['meta'] }}</span>@endif
                                </span>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            @endif
        </div>
    @endif
</div>

<div class="d-field">
    @if ($label)<span class="d-label">{{ $label }}</span>@endif

    <x-select
        size="sm"
        :placeholder="__('admin.widgets.add_product')"
        :options="$options"
        wire:model.live="search"
        :aria-label="__('admin.widgets.add_product')"
        wire:change="add($event.target.value)"
    />

    <div style="display: grid; gap: 8px; margin-top: 4px;"
         x-data="sortable({ wire: $wire, method: 'reorder' })">
        @forelse ($chosen as $i => $product)
            <div class="d-file" data-sortable-key="{{ $product->id }}" draggable="true"
                 @dragstart="start($event, {{ $i }})" @dragover="over($event)" @drop="drop($event, {{ $i }})"
                 style="cursor: grab;">
                <span class="a-grip"><x-icon name="grip-vertical" size="16" /></span>
                <x-image-frame :src="$product->getFirstMediaUrl('gallery')" style="width: 36px; height: 36px; border-radius: 6px; flex: none;" />
                <span style="min-width: 0; flex: 1;">
                    <span class="d-file__name" style="display: block;">{{ $product->getTranslation('name', app()->getLocale(), false) ?: $product->sku }}</span>
                    <span class="d-file__meta a-mono">{{ $product->sku }}</span>
                </span>
                <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="remove({{ $product->id }})" aria-label="{{ __('admin.widgets.remove') }}">
                    <x-icon name="x" size="16" />
                </button>
            </div>
        @empty
            <span class="d-help">{{ __('admin.widgets.no_products') }}</span>
        @endforelse
    </div>
</div>

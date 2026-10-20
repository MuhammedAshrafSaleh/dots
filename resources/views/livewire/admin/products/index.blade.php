@php
    use Illuminate\Support\Facades\Route;
    $stockTone = ['in' => 'success', 'low' => 'warning', 'order' => 'outline'];
    $statusTone = ['active' => 'success', 'draft' => 'neutral', 'archived' => 'outline'];
    $hasEdit = Route::has('admin.products.edit');
    $editUrl = fn ($p) => $hasEdit ? route('admin.products.edit', $p) : null;
    $pageIds = $rows->pluck('id')->map(fn ($i) => (int) $i)->all();
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.products.title')">
        <x-slot:actions>
            @if (Route::has('admin.products.import'))
                @can('products.import')
                    <x-button variant="outline" icon="upload" href="{{ route('admin.products.import') }}" wire:navigate>{{ __('admin.import_products.title') }}</x-button>
                @endcan
            @endif
            @if (Route::has('admin.products.create'))
                @can('products.create')
                    <x-button icon="plus" href="{{ route('admin.products.create') }}" wire:navigate>{{ __('admin.products.add') }}</x-button>
                @endcan
            @endif
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div style="padding: 0 18px;">
            <x-tabs
                :value="$tab"
                :tabs="[
                    ['id' => 'all', 'label' => __('admin.products.tab_all'), 'count' => $counts['all']],
                    ['id' => 'active', 'label' => __('admin.products.tab_active'), 'count' => $counts['active']],
                    ['id' => 'draft', 'label' => __('admin.products.tab_draft'), 'count' => $counts['draft']],
                    ['id' => 'archived', 'label' => __('admin.products.tab_archived'), 'count' => $counts['archived']],
                ]"
                x-on:tab-changed="$wire.set('tab', $event.detail)"
            />
        </div>

        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.products.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.products.search')" />
            <x-select size="sm" :aria-label="__('admin.products.all_categories')" :placeholder="__('admin.products.all_categories')" wire:model.live="category" :options="$categories" />
            <x-select size="sm" :aria-label="__('admin.products.all_brands')" :placeholder="__('admin.products.all_brands')" wire:model.live="brand" :options="$brands" />
            <x-select size="sm" :aria-label="__('admin.products.any_stock')" :placeholder="__('admin.products.any_stock')" wire:model.live="stock"
                :options="[['value' => 'in', 'label' => __('admin.products.stock_in')], ['value' => 'low', 'label' => __('admin.products.stock_low')], ['value' => 'order', 'label' => __('admin.products.stock_order')]]" />
        </div>

        @if (count($selected) > 0)
            <x-admin.bulk-bar :count="count($selected)" clear="clearSelection">
                @can('products.publish')
                    <x-button variant="ghost" icon="eye" wire:click="bulkPublish">{{ __('admin.products.publish') }}</x-button>
                    <x-button variant="ghost" icon="archive" wire:click="bulkArchive">{{ __('admin.products.archive') }}</x-button>
                @endcan
                @can('products.update')
                    <span style="display: inline-flex;">
                        <x-admin.menu icon="tag" :label="__('admin.products.set_badge')">
                            <button type="button" wire:click="bulkBadge('is_new')">{{ __('admin.products.badge_new') }}</button>
                            <button type="button" wire:click="bulkBadge('is_best_seller')">{{ __('admin.products.badge_best') }}</button>
                            <button type="button" wire:click="bulkBadge('is_smart_pick')">{{ __('admin.products.badge_smart') }}</button>
                            <hr>
                            <button type="button" wire:click="bulkBadge('none')">{{ __('admin.products.badge_none') }}</button>
                        </x-admin.menu>
                    </span>
                @endcan
                @can('products.delete')
                    <x-button variant="ghost" icon="trash-2" wire:click="bulkDelete" wire:confirm="{{ __('admin.products.delete_confirm') }}">{{ __('admin.products.delete') }}</x-button>
                @endcan
            </x-admin.bulk-bar>
        @endif

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th class="is-shrink">
                    <x-checkbox
                        x-on:change="$wire.toggleSelectAll(@js($pageIds))"
                        :checked="! $rows->isEmpty() && ! collect($pageIds)->diff($selected)->count()"
                        aria-label="select all"
                    />
                </th>
                <x-admin.sort-th column="name" :label="__('admin.products.col_product')" :current="$sortCol" :dir="$sortDir" />
                <th>SKU</th>
                <th>{{ __('admin.products.col_brand') }}</th>
                <th>{{ __('admin.products.col_category') }}</th>
                <x-admin.sort-th column="price" :label="__('admin.products.col_price')" :current="$sortCol" :dir="$sortDir" num />
                <x-admin.sort-th column="stock" :label="__('admin.products.col_stock')" :current="$sortCol" :dir="$sortDir" />
                <th>{{ __('admin.products.col_status') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $product)
                <tr wire:key="prod-{{ $product->id }}" @class(['is-click' => $hasEdit]) @if ($hasEdit) onclick="window.Livewire.navigate('{{ $editUrl($product) }}')" @endif>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-checkbox wire:model.live="selected" value="{{ $product->id }}" aria-label="select" />
                    </td>
                    <td>
                        <span style="display: flex; align-items: center; gap: 12px;">
                            <x-image-frame :src="$product->getFirstMediaUrl('gallery')" style="width: 40px; height: 40px; border-radius: 6px; border: 1px solid var(--border-1); flex: none;" />
                            <b style="font: 700 13px/1.35 var(--font-body);">{{ $product->getTranslation('name', app()->getLocale(), false) }}</b>
                        </span>
                    </td>
                    <td><span class="a-mono">{{ $product->sku }}</span></td>
                    <td>{{ $product->brand?->name }}</td>
                    <td>{{ $product->category?->getTranslation('name', app()->getLocale(), false) }}</td>
                    <td class="is-num">
                        @if ($product->price_on_request || $product->min_price === null)
                            <span style="color: var(--fg-3); font-weight: 600;">{{ __('admin.products.on_request') }}</span>
                        @else
                            <b>{{ number_format((float) $product->min_price, 2) }}</b>
                        @endif
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 8px;">
                            <x-badge :tone="$stockTone[$product->stock_status->value] ?? 'neutral'" :dot="$product->stock_status->value !== 'order'">{{ __('admin.products.stock_'.$product->stock_status->value) }}</x-badge>
                            <span class="a-count">{{ (int) ($product->stock_total ?? 0) }}</span>
                        </span>
                    </td>
                    <td><x-badge :tone="$statusTone[$product->status->value] ?? 'neutral'">{{ __('admin.products.status_'.$product->status->value) }}</x-badge></td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <span style="display: inline-flex;">
                            @if ($hasEdit)
                                @can('products.update')
                                    <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" href="{{ $editUrl($product) }}" wire:navigate aria-label="{{ __('admin.products.duplicate') }}"><x-icon name="pencil" size="16" /></a>
                                @endcan
                            @endif
                            <x-admin.menu>
                                @can('products.create')
                                    <button type="button" wire:click="duplicate({{ $product->id }})"><x-icon name="copy" size="16" />{{ __('admin.products.duplicate') }}</button>
                                @endcan
                                @can('products.delete')
                                    <hr>
                                    <button type="button" class="is-danger" wire:click="delete({{ $product->id }})" wire:confirm="{{ __('admin.products.delete_confirm') }}"><x-icon name="trash-2" size="16" />{{ __('admin.products.delete') }}</button>
                                @endcan
                            </x-admin.menu>
                        </span>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="package" :title="__('admin.products.empty_title')" :body="__('admin.products.empty_body')">
                    <x-slot:actions><x-button variant="outline" size="sm" wire:click="clearFilters">{{ __('admin.products.clear_filters') }}</x-button></x-slot:actions>
                </x-admin.empty>
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>

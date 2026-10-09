<div class="a-page">
    <x-admin.head :title="__('admin.brands.title')" :sub="__('admin.brands.subtitle')">
        <x-slot:actions>
            @can('brands.create')
                <x-button icon="plus" href="{{ route('admin.brands.create') }}" wire:navigate>{{ __('admin.brands.add') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.brands.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.brands.search')" />
            <x-select size="sm" :aria-label="__('admin.brands.all_origins')" :placeholder="__('admin.brands.all_origins')" wire:model.live="origin" :options="$origins" />
            <x-select size="sm" :aria-label="__('admin.brands.any_status')" :placeholder="__('admin.brands.any_status')" wire:model.live="status"
                :options="[['value' => 'on', 'label' => __('admin.brands.active')], ['value' => 'off', 'label' => __('admin.brands.inactive')]]" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <x-admin.sort-th column="name" :label="__('admin.brands.col_brand')" :current="$sortCol" :dir="$sortDir" />
                <th>{{ __('admin.brands.col_origin') }}</th>
                <x-admin.sort-th column="products" :label="__('admin.brands.col_products')" :current="$sortCol" :dir="$sortDir" num />
                <th class="is-shrink">{{ __('admin.brands.col_featured') }}</th>
                <th class="is-shrink">{{ __('admin.brands.col_active') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $brand)
                <tr class="is-click" wire:key="brand-{{ $brand->id }}" onclick="window.Livewire.navigate('{{ route('admin.brands.edit', $brand) }}')">
                    <td>
                        <span style="display: flex; align-items: center; gap: 12px;">
                            <x-image-frame :src="$brand->getFirstMediaUrl('logo')" style="width: 40px; height: 40px; border-radius: 6px; border: 1px solid var(--border-1); flex: none;" />
                            <span><b style="font-weight: 700;">{{ $brand->name }}</b>@if ($brand->is_authorised)<span class="a-sub">{{ __('admin.brands.authorised_distributor') }}</span>@endif</span>
                        </span>
                    </td>
                    <td>{{ $brand->getTranslation('origin_country', app()->getLocale(), false) ?: '—' }}</td>
                    <td class="is-num">{{ number_format($brand->products_count) }}</td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <input type="radio" name="a-feat-brand" @checked($brand->is_featured) wire:click="feature({{ $brand->id }})" style="width: 18px; height: 18px; accent-color: var(--ink-900);" aria-label="{{ __('admin.brands.col_featured') }}">
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleActive({{ $brand->id }})" @checked($brand->is_active) />
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <span style="display: inline-flex;">
                            @can('brands.update')
                                <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" href="{{ route('admin.brands.edit', $brand) }}" wire:navigate aria-label="{{ __('admin.brands.save') }}"><x-icon name="pencil" size="16" /></a>
                            @endcan
                            <x-admin.menu>
                                @if (\Illuminate\Support\Facades\Route::has('admin.products'))
                                    @can('products.view')
                                        <a href="{{ route('admin.products') }}?brand={{ $brand->id }}" wire:navigate><x-icon name="package" size="16" />{{ __('admin.brands.view_products') }}</a>
                                    @endcan
                                @endif
                                @can('brands.delete')
                                    <hr>
                                    <button type="button" class="is-danger" wire:click="delete({{ $brand->id }})" wire:confirm="{{ __('admin.brands.delete_confirm') }}"><x-icon name="trash-2" size="16" />{{ __('admin.brands.delete') }}</button>
                                @endcan
                            </x-admin.menu>
                        </span>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="award" :title="__('admin.brands.empty_title')" :body="__('admin.brands.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>

@php
    $types = [
        'chip' => ['tag', __('admin.attributes.display_chip')],
        'swatch' => ['palette', __('admin.attributes.display_swatch')],
        'button' => ['rectangle-horizontal', __('admin.attributes.display_button')],
    ];
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.attributes.title')" :sub="__('admin.attributes.subtitle')">
        <x-slot:actions>
            @can('attributes.create')
                <x-button icon="plus" href="{{ route('admin.attributes.create') }}" wire:navigate>{{ __('admin.attributes.add') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.attributes.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.attributes.search')" />
            <x-select size="sm" :aria-label="__('admin.attributes.any_display')" :placeholder="__('admin.attributes.any_display')"
                wire:model.live="displayType"
                :options="[['value' => 'chip', 'label' => __('admin.attributes.display_chip')], ['value' => 'swatch', 'label' => __('admin.attributes.display_swatch')], ['value' => 'button', 'label' => __('admin.attributes.display_button')]]" />
            <x-select size="sm" :aria-label="__('admin.attributes.usage_any')" :placeholder="__('admin.attributes.usage_any')"
                wire:model.live="usage"
                :options="[['value' => 'y', 'label' => __('admin.attributes.usage_variants')], ['value' => 'n', 'label' => __('admin.attributes.usage_filters')]]" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <x-admin.sort-th column="name" :label="__('admin.attributes.col_attribute')" :current="$sortCol" :dir="$sortDir" />
                <x-admin.sort-th column="display" :label="__('admin.attributes.col_display')" :current="$sortCol" :dir="$sortDir" />
                <th>{{ __('admin.attributes.col_unit') }}</th>
                <th>{{ __('admin.attributes.col_values') }}</th>
                <th class="is-shrink">{{ __('admin.attributes.col_filterable') }}</th>
                <th class="is-shrink">{{ __('admin.attributes.col_variants') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $attr)
                <tr class="is-click" wire:key="attr-{{ $attr->id }}" onclick="window.Livewire.navigate('{{ route('admin.attributes.edit', $attr) }}')">
                    <td>
                        <span><b style="font-weight: 700;">{{ $attr->getTranslation('name', app()->getLocale(), false) }}</b><span class="a-sub a-mono">{{ $attr->code }}</span></span>
                    </td>
                    <td>
                        <span style="display: inline-flex; gap: 6px; align-items: center; font-weight: 600;">
                            <x-icon :name="$types[$attr->display->value][0]" size="16" style="color: var(--fg-3);" />{{ $types[$attr->display->value][1] }}
                        </span>
                    </td>
                    <td>@if ($attr->unit)<span class="a-mono">{{ $attr->unit }}</span>@else<span class="a-count">—</span>@endif</td>
                    <td>
                        <span style="display: flex; gap: 4px; flex-wrap: wrap; align-items: center;">
                            @foreach ($attr->values as $v)
                                @if ($attr->display->value === 'swatch')
                                    <span title="{{ $v->getTranslation('label', app()->getLocale(), false) }}" style="width: 16px; height: 16px; border-radius: 50%; background: {{ $v->swatch }}; box-shadow: inset 0 0 0 1px var(--border-2);"></span>
                                @else
                                    <x-badge tone="outline">{{ \Illuminate\Support\Str::before($v->getTranslation('label', app()->getLocale(), false), ' ·') }}</x-badge>
                                @endif
                            @endforeach
                            @if ($attr->values_count > 4)<span class="a-count">+{{ $attr->values_count - 4 }}</span>@endif
                        </span>
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleFilterable({{ $attr->id }})" @checked($attr->is_filterable) />
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleVariants({{ $attr->id }})" @checked($attr->is_variant_axis) />
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <span style="display: inline-flex;">
                            @can('attributes.update')
                                <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" href="{{ route('admin.attributes.edit', $attr) }}" wire:navigate aria-label="{{ __('admin.attributes.save') }}"><x-icon name="pencil" size="16" /></a>
                            @endcan
                            <x-admin.menu>
                                @can('attributes.create')
                                    <button type="button" wire:click="duplicate({{ $attr->id }})"><x-icon name="copy" size="16" />{{ __('admin.attributes.duplicate') }}</button>
                                @endcan
                                @can('attributes.delete')
                                    <hr>
                                    <button type="button" class="is-danger" wire:click="delete({{ $attr->id }})" wire:confirm="{{ __('admin.attributes.delete_confirm') }}"><x-icon name="trash-2" size="16" />{{ __('admin.attributes.delete') }}</button>
                                @endcan
                            </x-admin.menu>
                        </span>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="sliders-horizontal" :title="__('admin.attributes.empty_title')" :body="__('admin.attributes.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>

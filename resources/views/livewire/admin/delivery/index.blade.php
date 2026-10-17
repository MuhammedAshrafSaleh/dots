@php
    $locale = app()->getLocale();
    $tx = fn ($m, $f) => $m->getTranslation($f, $locale, false);
@endphp
<div class="a-page" x-data="{ drawer: $wire.entangle('showDrawer') }">
    <x-admin.head :title="__('admin.delivery.title')" :sub="__('admin.delivery.subtitle')">
        <x-slot:actions>
            @can('delivery_methods.create')
                <x-button icon="plus" wire:click="openCreate">{{ __('admin.delivery.new') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    @if ($rows->isEmpty())
        <section class="a-card">
            <x-admin.empty icon="truck" :title="__('admin.delivery.empty_title')" :body="__('admin.delivery.empty_body')" />
        </section>
    @else
        <section class="a-card" style="overflow: hidden;">
            <div x-data="sortable({ wire: $wire, method: 'reorder' })">
                @foreach ($rows as $i => $method)
                    <div wire:key="dm-{{ $method->id }}" class="a-tree__row is-click"
                         data-sortable-key="{{ $method->id }}" draggable="true"
                         @dragstart="start($event, {{ $i }})" @dragover="over($event)" @drop="drop($event, {{ $i }})"
                         style="height: auto; min-height: 64px; padding: 12px 18px; gap: 14px; flex-wrap: wrap;"
                         wire:click="openEdit({{ $method->id }})">
                        <span class="a-grip" style="cursor: grab;" onclick="event.stopPropagation()"><x-icon name="grip-vertical" size="16" /></span>
                        <span class="a-count" style="width: 16px;">{{ $i + 1 }}</span>
                        <span class="a-kpi__ico" style="width: 36px; height: 36px;"><x-icon :name="$method->icon ?: 'truck'" size="18" /></span>
                        <span style="flex: 1 1 220px; min-width: 0;">
                            <b style="font: 800 14px/1.3 var(--font-body);">{{ $tx($method, 'name') }}</b>
                            <span class="a-sub">{{ $tx($method, 'description') }}</span>
                        </span>
                        <span style="font: 800 14px var(--font-body); min-width: 80px;">
                            @if ((float) $method->price > 0)
                                {{ $method->price_is_from ? __('admin.delivery.from').' ' : '' }}{{ number_format((float) $method->price, 2) }} {{ __('admin.orders.egp') }}
                            @else
                                {{ __('admin.delivery.free') }}
                            @endif
                            @if ($method->free_over_amount !== null)
                                <span class="a-sub">{{ __('admin.delivery.free_over') }} {{ number_format((float) $method->free_over_amount, 2) }}</span>
                            @endif
                        </span>
                        <span class="a-count" style="min-width: 110px;">
                            {{ $method->governorates_count === 0 || $method->governorates_count === $govCount
                                ? __('admin.delivery.all_governorates')
                                : $method->governorates_count.' '.__('admin.delivery.governorates') }}
                        </span>
                        <span onclick="event.stopPropagation()">
                            @can('delivery_methods.update')
                                <x-admin.switch wire:click="toggleActive({{ $method->id }})" :checked="(bool) $method->is_active" />
                            @endcan
                        </span>
                        @can('delivery_methods.delete')
                            <span onclick="event.stopPropagation()">
                                <x-admin.menu>
                                    <button type="button" class="is-danger" wire:click="delete({{ $method->id }})"
                                            wire:confirm="{{ __('admin.delivery.delete_confirm') }}">
                                        <x-icon name="trash-2" size="16" />{{ __('admin.delivery.delete') }}
                                    </button>
                                </x-admin.menu>
                            </span>
                        @endcan
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <x-admin.drawer show="drawer" :width="560" :title="$editingId ? $name[$locale] : __('admin.delivery.drawer_new')">
        <x-slot:foot>
            <x-button variant="outline" x-on:click="drawer = false">{{ __('admin.delivery.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.delivery.save') }}</x-button>
        </x-slot:foot>

        <x-admin.icon-pick :label="__('admin.delivery.icon')" model="icon" :value="$icon" />
        <x-admin.field-bilingual :label="__('admin.delivery.name')" model="name" />
        @error('name.en') <span class="d-help d-help--error">{{ $message }}</span> @enderror
        @error('name.ar') <span class="d-help d-help--error">{{ $message }}</span> @enderror
        <x-admin.field-bilingual :label="__('admin.delivery.description')" model="description" />

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
            <x-input :label="__('admin.delivery.price')" type="number" step="0.01" dir="ltr" wire:model="price" :help="__('admin.delivery.price_help')" :error="$errors->first('price')">
                <x-slot:trailing><span style="font: 700 13px var(--font-body);">EGP</span></x-slot:trailing>
            </x-input>
            <x-input :label="__('admin.delivery.order')" type="number" dir="ltr" wire:model="sort_order" :error="$errors->first('sort_order')" />
            <x-input :label="__('admin.delivery.free_over_amount')" type="number" step="0.01" dir="ltr" wire:model="free_over_amount" :error="$errors->first('free_over_amount')" optional>
                <x-slot:trailing><span style="font: 700 13px var(--font-body);">EGP</span></x-slot:trailing>
            </x-input>
        </div>

        <x-admin.switch wire:model="price_is_from" :label="__('admin.delivery.price_is_from')" :help="__('admin.delivery.price_is_from_help')" />
        <x-admin.switch wire:model="requires_address" :label="__('admin.delivery.requires_address')" :help="__('admin.delivery.requires_address_help')" />

        <div class="d-field">
            <span class="d-label" style="display: flex; justify-content: space-between;">
                {{ __('admin.delivery.available_governorates') }}
                <span class="a-count">{{ count($governorates) }} / {{ $govCount }}</span>
            </span>
            <div style="display: flex; gap: 8px; margin-bottom: 4px;">
                <button type="button" class="a-link" wire:click="selectAllGovs">{{ __('admin.delivery.select_all') }}</button>
                <button type="button" class="a-link" wire:click="clearGovs">{{ __('admin.delivery.clear') }}</button>
            </div>
            <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                @foreach ($govs as $gov)
                    @php $on = in_array((int) $gov->id, $governorates, true); @endphp
                    <x-chip :active="$on" wire:click="toggleGov({{ $gov->id }})" aria-pressed="{{ $on ? 'true' : 'false' }}">
                        @if ($on)<x-icon name="check" size="14" stroke="2" />@endif{{ $gov->getTranslation('name', $locale, false) }}
                    </x-chip>
                @endforeach
            </div>
            <span class="d-help">{{ __('admin.delivery.govs_help') }}</span>
        </div>

        <x-admin.field-bilingual :label="__('admin.delivery.note')" model="note" />
        <x-admin.switch wire:model="is_active" :label="__('admin.delivery.active')" :help="__('admin.delivery.active_help')" />
    </x-admin.drawer>
</div>

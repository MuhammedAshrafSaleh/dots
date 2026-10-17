@php
    $locale = app()->getLocale();
    $statusTone = ['new' => 'info', 'contacted' => 'warning', 'converted' => 'success', 'closed' => 'neutral'];
    $typeIcon = ['apartment' => 'building', 'villa' => 'home', 'office' => 'briefcase', 'retail_fnb' => 'store', 'hotel' => 'bed-double'];
    $statusTabs = ['new', 'contacted', 'converted', 'closed'];
@endphp
<div class="a-page" x-data="{ drawer: $wire.entangle('showDrawer') }">
    <x-admin.head :title="__('admin.consultations.title')" :sub="__('admin.consultations.subtitle')">
        <x-slot:actions>
            <x-button variant="outline" icon="download" wire:click="export">{{ __('admin.consultations.export') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div style="padding: 0 18px;">
            <x-tabs
                :value="$tab"
                :tabs="array_merge(
                    [['id' => 'all', 'label' => __('admin.consultations.tab_all'), 'count' => $counts['all']]],
                    array_map(fn ($s) => ['id' => $s, 'label' => __('admin.leads.status_'.$s), 'count' => $counts[$s]], $statusTabs),
                )"
                x-on:tab-changed="$wire.set('tab', $event.detail)"
            />
        </div>

        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.consultations.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.consultations.search')" />
            <x-select size="sm" :aria-label="__('admin.consultations.col_type')" :placeholder="__('admin.consultations.all_types')" wire:model.live="type" :options="$types" />
            <x-input size="sm" type="date" :aria-label="__('admin.consultations.from')" wire:model.live="from" dir="ltr" />
            <x-input size="sm" type="date" :aria-label="__('admin.consultations.to')" wire:model.live="to" dir="ltr" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.consultations.col_customer') }}</th>
                <th>{{ __('admin.consultations.col_type') }}</th>
                <th>{{ __('admin.consultations.col_details') }}</th>
                <th>{{ __('admin.consultations.col_date') }}</th>
                <th>{{ __('admin.consultations.col_status') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $req)
                <tr wire:key="consult-{{ $req->id }}" class="is-click" wire:click="openEdit({{ $req->id }})">
                    <td>
                        <span>
                            <b style="font-weight: 700;">{{ $req->name }}</b>
                            <span class="a-sub" dir="ltr" style="text-align: start;">{{ $req->mobile }}</span>
                        </span>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; white-space: nowrap;">
                            <x-icon :name="$typeIcon[$req->project_type->value] ?? 'building-2'" size="16" style="color: var(--fg-3);" />{{ __('admin.consultations.type_'.$req->project_type->value) }}
                        </span>
                    </td>
                    <td>
                        <span style="display: block; max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ __('admin.consultations.area_'.$req->area_range->value) }}@if ($req->service) · {{ $req->service->getTranslation('title', $locale, false) }}@endif
                        </span>
                    </td>
                    <td style="white-space: nowrap;">{{ $req->created_at?->translatedFormat('j M Y') }}</td>
                    <td><x-badge :tone="$statusTone[$req->status->value] ?? 'neutral'" dot>{{ __('admin.leads.status_'.$req->status->value) }}</x-badge></td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" style="color: var(--wa-600);" target="_blank" rel="noopener"
                           href="https://wa.me/{{ \App\Services\WhatsApp::normalizeNumber($req->mobile) }}"
                           aria-label="{{ __('admin.consultations.open_whatsapp') }}"><x-icon name="message-circle" size="16" /></a>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="calendar-check" :title="__('admin.consultations.empty_title')" :body="__('admin.consultations.empty_body')">
                    <x-slot:actions><x-button variant="outline" size="sm" wire:click="clearFilters">{{ __('admin.orders.clear_filters') }}</x-button></x-slot:actions>
                </x-admin.empty>
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>

    <x-admin.drawer show="drawer" :title="$name ?: __('admin.consultations.title')">
        <x-slot:foot>
            <x-button variant="outline" x-on:click="drawer = false">{{ __('admin.consultations.close') }}</x-button>
            @can('consultations.update')
                <x-button icon="check" wire:click="save">{{ __('admin.consultations.save') }}</x-button>
            @endcan
        </x-slot:foot>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
            <x-input :label="__('admin.consultations.drawer_name')" wire:model="name" :error="$errors->first('name')" />
            <x-input :label="__('admin.consultations.drawer_mobile')" wire:model="mobile" dir="ltr" inputmode="tel" :error="$errors->first('mobile')" />
        </div>

        <x-select :label="__('admin.consultations.status')" wire:model="status" :options="$statuses" />

        <x-input :label="__('admin.consultations.admin_notes')" multiline :rows="4" wire:model="admin_notes" :placeholder="__('admin.consultations.admin_notes_ph')" :error="$errors->first('admin_notes')" />
    </x-admin.drawer>
</div>

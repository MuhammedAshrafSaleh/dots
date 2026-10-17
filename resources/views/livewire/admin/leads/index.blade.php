@php
    $statusTone = ['new' => 'info', 'contacted' => 'warning', 'converted' => 'success', 'closed' => 'neutral'];
    $sourceIcon = [
        'product' => 'package', 'cart' => 'shopping-cart', 'instapay_order' => 'smartphone',
        'boq' => 'file-text', 'brand' => 'award', 'service' => 'wrench', 'project' => 'building-2',
        'article' => 'newspaper', 'order_help' => 'life-buoy', 'general' => 'message-circle',
    ];
    $statusTabs = ['new', 'contacted', 'converted', 'closed'];
@endphp
<div class="a-page" x-data="{ drawer: $wire.entangle('showDrawer') }">
    <x-admin.head :title="__('admin.leads.title')" :sub="__('admin.leads.subtitle')">
        <x-slot:actions>
            <x-button variant="outline" icon="download" wire:click="export">{{ __('admin.leads.export') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div style="padding: 0 18px;">
            <x-tabs
                :value="$tab"
                :tabs="array_merge(
                    [['id' => 'all', 'label' => __('admin.leads.tab_all'), 'count' => $counts['all']]],
                    array_map(fn ($s) => ['id' => $s, 'label' => __('admin.leads.status_'.$s), 'count' => $counts[$s]], $statusTabs),
                )"
                x-on:tab-changed="$wire.set('tab', $event.detail)"
            />
        </div>

        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.leads.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.leads.search')" />
            <x-select size="sm" :aria-label="__('admin.leads.col_source')" :placeholder="__('admin.leads.all_sources')" wire:model.live="source" :options="$sources" />
            <x-input size="sm" type="date" :aria-label="__('admin.leads.from')" wire:model.live="from" dir="ltr" />
            <x-input size="sm" type="date" :aria-label="__('admin.leads.to')" wire:model.live="to" dir="ltr" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.leads.col_customer') }}</th>
                <th>{{ __('admin.leads.col_source') }}</th>
                <th>{{ __('admin.leads.col_ref') }}</th>
                <th>{{ __('admin.leads.col_date') }}</th>
                <th>{{ __('admin.leads.col_status') }}</th>
                <th class="is-num">{{ __('admin.leads.col_notes') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $lead)
                <tr wire:key="lead-{{ $lead->id }}" class="is-click" wire:click="openEdit({{ $lead->id }})">
                    <td>
                        <span>
                            <b style="font-weight: 700; {{ $lead->contact_name ? '' : 'color: var(--fg-3);' }}">{{ $lead->contact_name ?: __('admin.leads.unknown') }}</b>
                            <span class="a-sub" dir="ltr" style="text-align: start;">{{ $lead->contact_mobile ?: '—' }}</span>
                        </span>
                    </td>
                    <td>
                        <span style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700; white-space: nowrap;">
                            <x-icon :name="$sourceIcon[$lead->source->value] ?? 'message-circle'" size="16" style="color: var(--fg-3);" />{{ __('admin.leads.source_'.$lead->source->value) }}
                        </span>
                    </td>
                    <td><span style="display: block; max-width: 340px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $this->refFor($lead) }}</span></td>
                    <td style="white-space: nowrap;">{{ $lead->created_at?->translatedFormat('j M Y') }}</td>
                    <td><x-badge :tone="$statusTone[$lead->status->value] ?? 'neutral'" dot>{{ __('admin.leads.status_'.$lead->status->value) }}</x-badge></td>
                    <td class="is-num">
                        @if ($lead->admin_notes)
                            <span style="display: inline-flex; gap: 4px; align-items: center;"><x-icon name="sticky-note" size="14" /></span>
                        @else
                            <span class="a-count">—</span>
                        @endif
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        @if ($lead->contact_mobile)
                            <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" style="color: var(--wa-600);" target="_blank" rel="noopener"
                               href="https://wa.me/{{ \App\Services\WhatsApp::normalizeNumber($lead->contact_mobile) }}"
                               aria-label="{{ __('admin.leads.open_whatsapp') }}"><x-icon name="message-circle" size="16" /></a>
                        @endif
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="message-circle" :title="__('admin.leads.empty_title')" :body="__('admin.leads.empty_body')">
                    <x-slot:actions><x-button variant="outline" size="sm" wire:click="clearFilters">{{ __('admin.orders.clear_filters') }}</x-button></x-slot:actions>
                </x-admin.empty>
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>

    <x-admin.drawer show="drawer" :title="$contact_name ?: __('admin.leads.unknown')">
        <x-slot:foot>
            <x-button variant="outline" x-on:click="drawer = false">{{ __('admin.leads.close') }}</x-button>
            @can('leads.update')
                <x-button icon="check" wire:click="save">{{ __('admin.leads.save') }}</x-button>
            @endcan
        </x-slot:foot>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
            <x-input :label="__('admin.leads.drawer_name')" wire:model="contact_name" :placeholder="__('admin.leads.drawer_name_ph')" :error="$errors->first('contact_name')" />
            <x-input :label="__('admin.leads.drawer_mobile')" wire:model="contact_mobile" dir="ltr" inputmode="tel" placeholder="01X XXXX XXXX" :error="$errors->first('contact_mobile')" />
        </div>

        <x-select :label="__('admin.leads.status')" wire:model="status" :options="$statuses" />

        <x-input :label="__('admin.leads.notes')" multiline :rows="4" wire:model="admin_notes" :placeholder="__('admin.leads.new_note')" :error="$errors->first('admin_notes')" />
    </x-admin.drawer>
</div>

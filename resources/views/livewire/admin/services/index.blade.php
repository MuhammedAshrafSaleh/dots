<div class="a-page">
    <x-admin.head :title="__('admin.services.title')" :sub="__('admin.services.subtitle')">
        <x-slot:actions>
            @can('services.create')
                <x-button icon="plus" href="{{ route('admin.services.create') }}" wire:navigate>{{ __('admin.services.add') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.services.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.services.search')" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.services.col_service') }}</th>
                <th class="is-shrink">{{ __('admin.services.col_footer') }}</th>
                <th class="is-shrink">{{ __('admin.services.col_active') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $service)
                <tr class="is-click" wire:key="service-{{ $service->id }}" onclick="window.Livewire.navigate('{{ route('admin.services.edit', $service) }}')">
                    <td>
                        <span style="display: flex; align-items: center; gap: 12px;">
                            <x-image-frame :src="$service->getFirstMediaUrl('image')" style="width: 40px; height: 40px; border-radius: 6px; border: 1px solid var(--border-1); flex: none;" />
                            <span>
                                <b style="font-weight: 700;">{{ $service->getTranslation('title', app()->getLocale(), false) ?: '—' }}</b>
                                @if ($service->number)<span class="a-sub">{{ $service->number }}</span>@endif
                            </span>
                        </span>
                    </td>
                    <td class="is-shrink">
                        @if ($service->show_in_footer)
                            <x-icon name="check" size="16" style="color: var(--ink-500);" />
                        @endif
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleActive({{ $service->id }})" @checked($service->is_active) />
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <span style="display: inline-flex;">
                            @can('services.update')
                                <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" href="{{ route('admin.services.edit', $service) }}" wire:navigate aria-label="{{ __('admin.services.edit') }}"><x-icon name="pencil" size="16" /></a>
                            @endcan
                            <x-admin.menu>
                                @can('services.delete')
                                    <button type="button" class="is-danger" wire:click="delete({{ $service->id }})" wire:confirm="{{ __('admin.services.delete_confirm') }}"><x-icon name="trash-2" size="16" />{{ __('admin.services.delete') }}</button>
                                @endcan
                            </x-admin.menu>
                        </span>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="briefcase" :title="__('admin.services.empty_title')" :body="__('admin.services.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>

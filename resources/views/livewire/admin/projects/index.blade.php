<div class="a-page">
    <x-admin.head :title="__('admin.projects.title')" :sub="__('admin.projects.subtitle')">
        <x-slot:actions>
            @can('projects.create')
                <x-button icon="plus" href="{{ route('admin.projects.create') }}" wire:navigate>{{ __('admin.projects.add') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <div role="tablist" class="d-tabs" style="flex: none;">
                <button role="tab" class="d-tab" aria-selected="{{ $statusTab === 'current' ? 'true' : 'false' }}" wire:click="$set('statusTab', 'current')" type="button">{{ __('admin.projects.tab_current') }}</button>
                <button role="tab" class="d-tab" aria-selected="{{ $statusTab === 'completed' ? 'true' : 'false' }}" wire:click="$set('statusTab', 'completed')" type="button">{{ __('admin.projects.tab_completed') }}</button>
            </div>
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.projects.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.projects.search')" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.projects.col_project') }}</th>
                <th class="is-shrink">{{ __('admin.projects.col_type') }}</th>
                @if ($statusTab === 'current')
                    <th class="is-shrink">{{ __('admin.projects.col_stage') }}</th>
                @else
                    <th class="is-shrink">{{ __('admin.projects.col_year') }}</th>
                @endif
                <th class="is-shrink">{{ __('admin.projects.col_active') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $project)
                <tr class="is-click" wire:key="project-{{ $project->id }}" onclick="window.Livewire.navigate('{{ route('admin.projects.edit', $project) }}')">
                    <td>
                        <span style="display: flex; align-items: center; gap: 12px;">
                            <x-image-frame :src="$project->getFirstMediaUrl('cover')" style="width: 40px; height: 40px; border-radius: 6px; border: 1px solid var(--border-1); flex: none;" />
                            <span>
                                <b style="font-weight: 700;">{{ $project->getTranslation('title', app()->getLocale(), false) ?: '—' }}</b>
                                @if ($project->getTranslation('location', app()->getLocale(), false))
                                    <span class="a-sub">{{ $project->getTranslation('location', app()->getLocale(), false) }}</span>
                                @endif
                            </span>
                        </span>
                    </td>
                    <td class="is-shrink">
                        <span class="a-sub">{{ __('admin.projects.type_'.$project->type->value) }}</span>
                    </td>
                    <td class="is-shrink">
                        @if ($statusTab === 'current')
                            <span class="a-sub">{{ __('admin.projects.stage_'.$project->stage->value) }}</span>
                        @else
                            <span class="a-sub">{{ $project->completed_year ?: '—' }}</span>
                        @endif
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleActive({{ $project->id }})" @checked($project->is_active) />
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <span style="display: inline-flex;">
                            @can('projects.update')
                                <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" href="{{ route('admin.projects.edit', $project) }}" wire:navigate aria-label="{{ __('admin.projects.edit') }}"><x-icon name="pencil" size="16" /></a>
                            @endcan
                            <x-admin.menu>
                                @can('projects.delete')
                                    <button type="button" class="is-danger" wire:click="delete({{ $project->id }})" wire:confirm="{{ __('admin.projects.delete_confirm') }}"><x-icon name="trash-2" size="16" />{{ __('admin.projects.delete') }}</button>
                                @endcan
                            </x-admin.menu>
                        </span>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="building-2" :title="__('admin.projects.empty_title')" :body="__('admin.projects.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>

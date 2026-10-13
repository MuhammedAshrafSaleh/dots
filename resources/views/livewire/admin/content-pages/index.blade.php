<div class="a-page">
    <x-admin.head :title="__('admin.content_pages.title')" :sub="__('admin.content_pages.subtitle')">
        <x-slot:actions>
            @can('content_pages.create')
                <x-button icon="plus" href="{{ route('admin.content-pages.create') }}" wire:navigate>{{ __('admin.content_pages.add') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.content_pages.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.content_pages.search')" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.content_pages.col_page') }}</th>
                <th class="is-shrink">{{ __('admin.content_pages.col_updated') }}</th>
                <th class="is-shrink">{{ __('admin.content_pages.col_footer') }}</th>
                <th class="is-shrink">{{ __('admin.content_pages.col_active') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $page)
                <tr class="is-click" wire:key="page-{{ $page->id }}" onclick="window.Livewire.navigate('{{ route('admin.content-pages.edit', $page) }}')">
                    <td>
                        <span>
                            <b style="font-weight: 700;">{{ $page->getTranslation('title', app()->getLocale(), false) ?: '—' }}</b>
                            <span class="a-sub">{{ $page->key }}</span>
                        </span>
                    </td>
                    <td class="is-shrink" style="white-space: nowrap;">
                        <span class="a-sub">{{ $page->updated_at->diffForHumans() }}</span>
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleFooter({{ $page->id }})" @checked($page->show_in_footer) />
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleActive({{ $page->id }})" @checked($page->is_active) />
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <span style="display: inline-flex;">
                            @can('content_pages.update')
                                <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" href="{{ route('admin.content-pages.edit', $page) }}" wire:navigate aria-label="{{ __('admin.content_pages.edit') }}"><x-icon name="pencil" size="16" /></a>
                            @endcan
                            <x-admin.menu>
                                @can('content_pages.delete')
                                    <button type="button" class="is-danger" wire:click="delete({{ $page->id }})" wire:confirm="{{ __('admin.content_pages.delete_confirm') }}"><x-icon name="trash-2" size="16" />{{ __('admin.content_pages.delete') }}</button>
                                @endcan
                            </x-admin.menu>
                        </span>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="file-text" :title="__('admin.content_pages.empty_title')" :body="__('admin.content_pages.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>

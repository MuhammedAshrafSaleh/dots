<div class="a-page">
    <x-admin.head :title="__('admin.blog.title')" :sub="__('admin.blog.subtitle')">
        <x-slot:actions>
            @can('post_categories.manage')
                <x-button variant="outline" icon="tags" href="{{ route('admin.blog.taxonomy') }}" wire:navigate>{{ __('admin.blog.tax_manage') }}</x-button>
            @endcan
            @can('posts.create')
                <x-button icon="plus" href="{{ route('admin.blog.create') }}" wire:navigate>{{ __('admin.blog.add') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            {{-- Status tabs --}}
            <div role="tablist" class="d-tabs" style="flex: none;">
                @foreach (['all' => __('admin.blog.tab_all'), 'draft' => __('admin.blog.tab_draft'), 'published' => __('admin.blog.tab_published'), 'scheduled' => __('admin.blog.tab_scheduled')] as $tab => $label)
                    <button role="tab" class="d-tab" aria-selected="{{ $statusTab === $tab ? 'true' : 'false' }}" wire:click="$set('statusTab', '{{ $tab }}')" type="button">{{ $label }}</button>
                @endforeach
            </div>

            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.blog.search')" wire:model.live.debounce.300ms="search" :aria-label="__('admin.blog.search')" />

            {{-- Category filter --}}
            <select class="d-select d-select--sm" wire:model.live="categoryFilter" aria-label="{{ __('admin.blog.filter_category') }}" style="min-width: 0; width: auto;">
                <option value="">{{ __('admin.blog.filter_category') }}</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->getTranslation('name', app()->getLocale(), false) }}</option>
                @endforeach
            </select>

            {{-- Featured filter --}}
            <select class="d-select d-select--sm" wire:model.live="featuredFilter" aria-label="{{ __('admin.blog.filter_featured') }}" style="min-width: 0; width: auto;">
                <option value="">{{ __('admin.blog.filter_featured') }}</option>
                <option value="y">{{ __('admin.blog.featured_yes') }}</option>
                <option value="n">{{ __('admin.blog.featured_no') }}</option>
            </select>
        </div>

        {{-- Bulk actions --}}
        @if (count($selected) > 0)
            <div class="a-bar" style="padding: 8px 0; border-bottom: 1px solid var(--border-1);">
                <span class="a-sub">{{ __('admin.blog.selected', ['n' => count($selected)]) }}</span>
                @can('posts.update')
                    <x-button size="sm" variant="outline" icon="eye" wire:click="bulkPublish">{{ __('admin.blog.bulk_publish') }}</x-button>
                    <x-button size="sm" variant="outline" icon="file" wire:click="bulkDraft">{{ __('admin.blog.bulk_draft') }}</x-button>
                @endcan
                <x-button size="sm" variant="ghost" wire:click="clearSelection">{{ __('admin.widgets.cancel') }}</x-button>
            </div>
        @endif

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th style="width: 30px;">
                    <input type="checkbox" wire:click="toggleSelectAll({{ $rows->pluck('id')->toJson() }})"
                        :checked="{{ $rows->pluck('id')->diff(collect($selected))->isEmpty() && $rows->isNotEmpty() ? 'true' : 'false' }}"
                        aria-label="{{ __('admin.widgets.select_all') }}" />
                </th>
                <th>{{ __('admin.blog.col_post') }}</th>
                <th class="is-shrink">{{ __('admin.blog.col_category') }}</th>
                <th class="is-shrink">{{ __('admin.blog.col_status') }}</th>
                <th class="is-shrink">{{ __('admin.blog.col_featured') }}</th>
                <th class="is-shrink">{{ __('admin.blog.col_date') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $post)
                <tr class="is-click" wire:key="post-{{ $post->id }}" onclick="window.Livewire.navigate('{{ route('admin.blog.edit', $post) }}')">
                    <td onclick="event.stopPropagation()" style="width: 30px;">
                        <input type="checkbox" wire:model.live="selected" value="{{ $post->id }}" aria-label="{{ __('admin.widgets.select') }}" />
                    </td>
                    <td>
                        <span style="display: flex; align-items: center; gap: 12px;">
                            <x-image-frame :src="$post->getFirstMediaUrl('cover')" style="width: 64px; height: 44px; border-radius: 6px; border: 1px solid var(--border-1); flex: none;" />
                            <span>
                                <b style="font-weight: 700;">{{ $post->getTranslation('title', app()->getLocale(), false) ?: '—' }}</b>
                                @if ($post->read_minutes)
                                    <span class="a-sub">{{ $post->read_minutes }} {{ __('admin.blog.min_read') }}</span>
                                @endif
                            </span>
                        </span>
                    </td>
                    <td class="is-shrink">
                        <span class="a-sub">{{ $post->category?->getTranslation('name', app()->getLocale(), false) ?: '—' }}</span>
                    </td>
                    <td class="is-shrink">
                        @php
                            $statusClass = match($post->status->value) {
                                'published' => 'is-success',
                                'scheduled' => 'is-info',
                                default => '',
                            };
                        @endphp
                        <span class="a-pill {{ $statusClass }}">{{ __('admin.blog.status_'.$post->status->value) }}</span>
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <x-admin.switch wire:click="toggleFeatured({{ $post->id }})" @checked($post->is_featured) />
                    </td>
                    <td class="is-shrink">
                        @if ($post->published_at)
                            <span class="a-mono">{{ $post->published_at->format('Y-m-d') }}</span>
                        @else
                            <span class="a-count">—</span>
                        @endif
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        <span style="display: inline-flex;">
                            @can('posts.update')
                                <a class="d-btn d-btn--ghost d-btn--icon d-btn--sm" href="{{ route('admin.blog.edit', $post) }}" wire:navigate aria-label="{{ __('admin.blog.edit') }}"><x-icon name="pencil" size="16" /></a>
                            @endcan
                            <x-admin.menu>
                                @can('posts.delete')
                                    <button type="button" class="is-danger" wire:click="delete({{ $post->id }})" wire:confirm="{{ __('admin.blog.delete_confirm') }}"><x-icon name="trash-2" size="16" />{{ __('admin.blog.delete') }}</button>
                                @endcan
                            </x-admin.menu>
                        </span>
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="newspaper" :title="__('admin.blog.empty_title')" :body="__('admin.blog.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>
</div>

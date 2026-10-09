<div class="a-page">
    <x-admin.head :title="__('admin.blog.tax_title')" :sub="__('admin.blog.tax_subtitle')">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.blog') }}" wire:navigate>{{ __('admin.blog.title') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <div class="a-card" style="padding: 0 18px;">
        <div role="tablist" class="d-tabs">
            <button role="tab" class="d-tab" aria-selected="{{ $activeTab === 'cat' ? 'true' : 'false' }}" wire:click="$set('activeTab', 'cat')" type="button">
                {{ __('admin.blog.tax_tab_cats') }} <span class="a-count" style="margin-inline-start: 4px;">{{ count($cats) }}</span>
            </button>
            <button role="tab" class="d-tab" aria-selected="{{ $activeTab === 'tag' ? 'true' : 'false' }}" wire:click="$set('activeTab', 'tag')" type="button">
                {{ __('admin.blog.tax_tab_tags') }} <span class="a-count" style="margin-inline-start: 4px;">{{ count($tags) }}</span>
            </button>
        </div>
    </div>

    {{-- Categories --}}
    @if ($activeTab === 'cat')
        <section class="a-card">
            <div class="a-bar">
                <span class="a-grow"></span>
                @can('post_categories.manage')
                    <x-button size="sm" icon="plus" wire:click="addCategory">{{ __('admin.blog.add_category') }}</x-button>
                @endcan
            </div>

            <x-admin.table :empty="empty($cats)">
                <x-slot:head>
                    <th>{{ __('admin.blog.tax_col_name_en') }}</th>
                    <th>{{ __('admin.blog.tax_col_name_ar') }}</th>
                    <th>{{ __('admin.blog.tax_col_slug_en') }}</th>
                    <th>{{ __('admin.blog.tax_col_slug_ar') }}</th>
                    <th class="is-shrink">{{ __('admin.blog.tax_col_order') }}</th>
                    <th class="is-shrink is-num">{{ __('admin.blog.tax_col_posts') }}</th>
                    <th class="is-shrink"></th>
                </x-slot:head>

                @foreach ($cats as $i => $cat)
                    <tr wire:key="cat-{{ $i }}">
                        <td>
                            <input class="d-input d-input--sm" style="width: 100%;" type="text" wire:model="cats.{{ $i }}.name_en" dir="ltr" placeholder="English" />
                            @error("cats.$i.name_en") <span class="d-help d-help--error">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <input class="d-input d-input--sm" style="width: 100%;" type="text" wire:model="cats.{{ $i }}.name_ar" dir="rtl" placeholder="العربية" />
                            @error("cats.$i.name_ar") <span class="d-help d-help--error">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <input class="d-input d-input--sm" style="width: 130px; font-family: var(--font-mono); font-size: 12px;" type="text" wire:model="cats.{{ $i }}.slug_en" dir="ltr" placeholder="slug-en" />
                            @error("cats.$i.slug_en") <span class="d-help d-help--error">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <input class="d-input d-input--sm" style="width: 130px; font-family: var(--font-mono); font-size: 12px;" type="text" wire:model="cats.{{ $i }}.slug_ar" dir="rtl" placeholder="الرابط" />
                            @error("cats.$i.slug_ar") <span class="d-help d-help--error">{{ $message }}</span> @enderror
                        </td>
                        <td class="is-shrink">
                            <input class="d-input d-input--sm" style="width: 60px;" type="number" wire:model="cats.{{ $i }}.sort_order" dir="ltr" min="0" />
                        </td>
                        <td class="is-shrink is-num">
                            <span class="a-count">{{ $cat['count'] }}</span>
                        </td>
                        <td class="is-shrink">
                            <span style="display: inline-flex; gap: 2px;">
                                @can('post_categories.manage')
                                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="saveCategory({{ $i }})" aria-label="{{ __('admin.widgets.save') }}"><x-icon name="check" size="16" /></button>
                                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="deleteCategory({{ $i }})" wire:confirm="{{ __('admin.blog.tax_delete_confirm') }}" aria-label="{{ __('admin.blog.delete') }}" style="color: var(--error-600);"><x-icon name="trash-2" size="16" /></button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @endforeach

                <x-slot:emptyState>
                    <x-admin.empty icon="tags" :title="__('admin.blog.tax_empty_cats')" />
                </x-slot:emptyState>
            </x-admin.table>
        </section>
    @endif

    {{-- Tags --}}
    @if ($activeTab === 'tag')
        <section class="a-card">
            <div class="a-bar">
                <span class="a-grow"></span>
                @can('post_categories.manage')
                    <x-button size="sm" icon="plus" wire:click="addTag">{{ __('admin.blog.add_tag') }}</x-button>
                @endcan
            </div>

            <x-admin.table :empty="empty($tags)">
                <x-slot:head>
                    <th>{{ __('admin.blog.tax_col_name_en') }}</th>
                    <th>{{ __('admin.blog.tax_col_name_ar') }}</th>
                    <th>{{ __('admin.blog.tax_col_slug') }}</th>
                    <th class="is-shrink is-num">{{ __('admin.blog.tax_col_posts') }}</th>
                    <th class="is-shrink"></th>
                </x-slot:head>

                @foreach ($tags as $i => $tag)
                    <tr wire:key="tag-{{ $i }}">
                        <td>
                            <input class="d-input d-input--sm" style="width: 100%;" type="text" wire:model="tags.{{ $i }}.name_en" dir="ltr" placeholder="English" />
                            @error("tags.$i.name_en") <span class="d-help d-help--error">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <input class="d-input d-input--sm" style="width: 100%;" type="text" wire:model="tags.{{ $i }}.name_ar" dir="rtl" placeholder="العربية" />
                            @error("tags.$i.name_ar") <span class="d-help d-help--error">{{ $message }}</span> @enderror
                        </td>
                        <td>
                            <input class="d-input d-input--sm" style="width: 160px; font-family: var(--font-mono); font-size: 12px;" type="text" wire:model="tags.{{ $i }}.slug" dir="ltr" placeholder="tag-slug" />
                            @error("tags.$i.slug") <span class="d-help d-help--error">{{ $message }}</span> @enderror
                        </td>
                        <td class="is-shrink is-num">
                            <span class="a-count">{{ $tag['count'] }}</span>
                        </td>
                        <td class="is-shrink">
                            <span style="display: inline-flex; gap: 2px;">
                                @can('post_categories.manage')
                                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="saveTag({{ $i }})" aria-label="{{ __('admin.widgets.save') }}"><x-icon name="check" size="16" /></button>
                                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="deleteTag({{ $i }})" wire:confirm="{{ __('admin.blog.tax_delete_confirm') }}" aria-label="{{ __('admin.blog.delete') }}" style="color: var(--error-600);"><x-icon name="trash-2" size="16" /></button>
                                @endcan
                            </span>
                        </td>
                    </tr>
                @endforeach

                <x-slot:emptyState>
                    <x-admin.empty icon="tags" :title="__('admin.blog.tax_empty_tags')" />
                </x-slot:emptyState>
            </x-admin.table>
        </section>
    @endif
</div>

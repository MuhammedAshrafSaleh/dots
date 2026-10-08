@php
    $levels = __('admin.categories.levels');
    $imageUrl = fn ($collection, $upload) => $upload
        ? $upload->temporaryUrl()
        : (! ($remove[$collection] ?? false) && $selected && ($u = $selected->getFirstMediaUrl($collection)) !== '' ? $u : null);
@endphp
<div class="a-page">
    <x-admin.head :title="__('admin.categories.title')" :sub="__('admin.categories.subtitle')">
        <x-slot:actions>
            <x-button variant="outline" wire:click="expandAll">{{ __('admin.categories.expand_all') }}</x-button>
            @can('categories.create')
                <x-button icon="plus" wire:click="addMain">{{ __('admin.categories.add_main') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <div class="a-split" style="--a-aside: 520px;">
        <section class="a-card" style="overflow: hidden;">
            <div class="a-tree">
                @include('livewire.admin.categories._nodes', ['nodes' => $tree, 'level' => 1])
            </div>
        </section>

        <x-admin.card :title="$selected ? $selected->getTranslation('name', app()->getLocale(), false) : __('admin.categories.select_prompt')" icon="pencil">
            @if (! $selected)
                <span class="d-help">{{ __('admin.categories.click_to_edit') }}</span>
            @else
                <div style="display: grid; gap: 16px;" wire:key="panel-{{ $selectedId }}">
                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        <x-badge tone="neutral">{{ $levels[$level - 1] ?? '' }}</x-badge>
                        @if ($selected->parent)<x-badge tone="outline">{{ __('admin.categories.in_parent') }} {{ $selected->parent->getTranslation('name', app()->getLocale(), false) }}</x-badge>@endif
                    </div>

                    <x-admin.field-bilingual :label="__('admin.categories.name')" model="name" />
                    @error('name.ar')<span class="d-help d-help--error">{{ $message }}</span>@enderror
                    @error('name.en')<span class="d-help d-help--error">{{ $message }}</span>@enderror

                    <x-admin.slugs model="slug" path="/c" generate="generateSlug" />
                    @error('slug.ar')<span class="d-help d-help--error">{{ $message }}</span>@enderror
                    @error('slug.en')<span class="d-help d-help--error">{{ $message }}</span>@enderror

                    @if ($level === 1)
                        <x-admin.icon-pick :label="__('admin.categories.icon')" model="icon" :value="$icon" />
                    @endif

                    <x-admin.field-bilingual :label="__('admin.categories.description')" model="description" multiline :rows="3" />

                    <x-admin.image :label="__('admin.categories.card_image')" :url="$imageUrl('image', $imageUpload)" model="imageUpload" remove="removeMedia('image')" ratio="4 / 3" />
                    <x-admin.image :label="__('admin.categories.hero_image')" :url="$imageUrl('hero', $heroUpload)" model="heroUpload" remove="removeMedia('hero')" ratio="16 / 6" />

                    <div class="a-side__group" style="padding: 12px 0 0; border-top: 1px solid var(--border-1);">{{ __('admin.categories.buying_guide') }}</div>
                    <x-admin.field-bilingual :label="__('admin.categories.buying_guide_title')" model="buying_guide_title" />
                    <x-admin.repeater add="addFaq" reorder="reorderFaqs" :add-label="__('admin.categories.add_question')">
                        @foreach ($faqs as $key => $faq)
                            <x-admin.repeater-row wire:key="faq-{{ $key }}" :index="$loop->index" :key="$key" remove="removeFaq" sortable>
                                <x-admin.field-bilingual :label="__('admin.categories.question')" :model="'faqs.'.$key.'.question'" />
                                <x-admin.field-bilingual :label="__('admin.categories.answer')" :model="'faqs.'.$key.'.answer'" multiline :rows="2" />
                            </x-admin.repeater-row>
                        @endforeach
                    </x-admin.repeater>

                    <div class="a-side__group" style="padding: 12px 0 0; border-top: 1px solid var(--border-1);">{{ __('admin.categories.top_picks') }}</div>
                    <livewire:admin.widgets.product-picker :key="'picks-'.$selectedId" wire:model="featuredIds" />

                    <div class="a-side__group" style="padding: 12px 0 0; border-top: 1px solid var(--border-1);">{{ __('admin.categories.visibility') }}</div>
                    <x-admin.switch wire:model="is_active" :label="__('admin.categories.active')" :help="__('admin.categories.active_help')" />
                    <x-admin.switch wire:model="show_on_home" :label="__('admin.categories.show_home')" />
                    <x-admin.switch wire:model="show_in_footer" :label="__('admin.categories.show_footer')" />

                    <x-admin.seo title-model="meta_title" desc-model="meta_description" />

                    <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                        @can('categories.update')
                            <x-button icon="check" wire:click="save">{{ __('admin.categories.save') }}</x-button>
                        @endcan
                        @can('categories.delete')
                            <x-button variant="ghost" icon="trash-2" style="color: var(--error-600);" wire:click="delete({{ $selectedId }})" wire:confirm="{{ __('admin.categories.delete_confirm') }}">{{ __('admin.categories.delete') }}</x-button>
                        @endcan
                    </div>
                </div>
            @endif
        </x-admin.card>
    </div>
</div>

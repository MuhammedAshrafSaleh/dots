@php
    $tabs = ['basic', 'items', 'gallery', 'faq', 'seo'];
@endphp
<div class="a-page">
    <x-admin.head :title="$service ? $service->getTranslation('title', app()->getLocale(), false) : __('admin.services.new')">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.services') }}" wire:navigate>{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.services.save') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <div class="a-split" style="--a-aside: 280px;">
        {{-- Main column with tabs --}}
        <div style="min-width: 0;">
            <div role="tablist" class="d-tabs" style="margin-bottom: 16px;">
                @foreach ($tabs as $t)
                    @php $tabHasError = in_array($t, $errorTabs, true); @endphp
                    <button
                        role="tab"
                        class="d-tab @if ($tabHasError) is-error @endif"
                        aria-selected="{{ $activeTab === $t ? 'true' : 'false' }}"
                        wire:click="$set('activeTab', '{{ $t }}')"
                        type="button">
                        {{ __('admin.services.tab_'.$t) }}@if ($tabHasError)<span aria-hidden="true" style="color: var(--error-600); margin-inline-start: 4px;">●</span>@endif
                    </button>
                @endforeach
            </div>

            {{-- Basic --}}
            <div @class(['a-hidden' => $activeTab !== 'basic'])>
                <x-admin.card :title="__('admin.services.tab_basic')">
                    <div style="display: grid; gap: 18px;">
                        <x-admin.field-bilingual :label="__('admin.services.title')" model="title" required />

                        <div class="d-field">
                            <span class="d-label" style="display: flex; justify-content: space-between; gap: 8px;">
                                {{ __('admin.widgets.slug') }}
                                <button type="button" class="a-link" wire:click="generateSlugs" style="font-size: 12px;"><x-icon name="refresh-cw" size="13" /> {{ __('admin.widgets.auto_generate') }}</button>
                            </span>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <x-input dir="ltr" placeholder="EN" wire:model="slug.en" :error="$errors->first('slug.en')" />
                                <x-input dir="rtl" placeholder="AR" wire:model="slug.ar" :error="$errors->first('slug.ar')" />
                            </div>
                        </div>

                        <x-input :label="__('admin.services.number')" wire:model="number.en" dir="ltr" :error="$errors->first('number.en')" />

                        <x-admin.field-bilingual :label="__('admin.services.excerpt')" model="excerpt" multiline :rows="3" />
                        <x-admin.rich :label="__('admin.services.description')" model="description" :min-h="200" />
                        <x-admin.field-bilingual :label="__('admin.services.included_title')" model="included_title" />
                        <x-admin.field-bilingual :label="__('admin.services.duration_text')" model="duration_text" />
                    </div>
                </x-admin.card>
            </div>

            {{-- Items (Included) --}}
            <div @class(['a-hidden' => $activeTab !== 'items'])>
                <x-admin.card :title="__('admin.services.tab_items')">
                    <x-slot:action><span class="a-count">{{ __('admin.services.items_hint') }}</span></x-slot:action>
                    <div class="a-scroll">
                        <table class="a-table" style="min-width: 580px;">
                            <thead><tr>
                                <th class="is-shrink"></th>
                                <th>{{ __('admin.services.item_text') }} · EN</th>
                                <th>{{ __('admin.services.item_text') }} · {{ __('admin.widgets.ar') }}</th>
                                <th class="is-shrink"></th>
                            </tr></thead>
                            <tbody x-data="sortable({ wire: $wire, method: 'reorderItems' })">
                                @foreach ($items as $key => $item)
                                    <tr wire:key="item-{{ $key }}" data-sortable-key="{{ $key }}" draggable="true"
                                        @dragstart="start($event, {{ $loop->index }})" @dragover="over($event)" @drop="drop($event, {{ $loop->index }})">
                                        <td class="is-shrink"><span class="a-grip" style="cursor: grab;"><x-icon name="grip-vertical" size="16" /></span></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="ltr" wire:model="items.{{ $key }}.text.en" aria-label="Text EN"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="rtl" wire:model="items.{{ $key }}.text.ar" aria-label="Text AR"></td>
                                        <td class="is-shrink"><button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="removeItem('{{ $key }}')" aria-label="{{ __('admin.widgets.remove') }}"><x-icon name="trash-2" size="16" /></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top: 12px;"><x-button size="sm" variant="outline" icon="plus" wire:click="addItem">{{ __('admin.services.add_item') }}</x-button></div>
                </x-admin.card>
            </div>

            {{-- Gallery --}}
            <div @class(['a-hidden' => $activeTab !== 'gallery'])>
                <x-admin.card :title="__('admin.services.tab_gallery')">
                    <x-admin.gallery model="galleryUploads" :items="$galleryItems" reorder="reorderGallery" remove="removeGalleryImage" :help="__('admin.services.gallery_hint')" />
                </x-admin.card>
            </div>

            {{-- FAQ --}}
            <div @class(['a-hidden' => $activeTab !== 'faq'])>
                <x-admin.card :title="__('admin.services.tab_faq')">
                    <div style="display: grid; gap: 16px;">
                        @foreach ($faqs as $key => $faq)
                            <div wire:key="faq-{{ $key }}" class="a-card" style="padding: 16px; display: grid; gap: 12px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px;">
                                    <span style="font-weight: 600;">{{ __('admin.services.faq_item', ['n' => $loop->iteration]) }}</span>
                                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="removeFaq('{{ $key }}')" aria-label="{{ __('admin.widgets.remove') }}"><x-icon name="trash-2" size="16" /></button>
                                </div>
                                <x-admin.field-bilingual :label="__('admin.services.faq_question')" model="faqs.{{ $key }}.question" />
                                <x-admin.field-bilingual :label="__('admin.services.faq_answer')" model="faqs.{{ $key }}.answer" multiline :rows="3" />
                            </div>
                        @endforeach
                    </div>
                    <div style="margin-top: 12px;"><x-button size="sm" variant="outline" icon="plus" wire:click="addFaq">{{ __('admin.services.add_faq') }}</x-button></div>
                </x-admin.card>
            </div>

            {{-- SEO --}}
            <div @class(['a-hidden' => $activeTab !== 'seo'])>
                <x-admin.seo title-model="meta_title" desc-model="meta_description" />
            </div>
        </div>

        {{-- Sidebar --}}
        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.services.visibility')">
                <div style="display: grid; gap: 16px;">
                    <x-admin.switch wire:model="is_active" :label="__('admin.services.active')" />
                    <x-admin.switch wire:model="show_in_footer" :label="__('admin.services.show_in_footer')" />
                    <x-input type="number" dir="ltr" :label="__('admin.services.sort_order')" wire:model="sort_order" :error="$errors->first('sort_order')" />
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.services.cover_image')">
                <x-admin.image :url="$imageUrl" model="imageUpload" remove="removeServiceImage" ratio="4 / 3" />
            </x-admin.card>

            @if ($service)
                <x-admin.card>
                    <x-button variant="ghost" size="sm" icon="trash-2" wire:click="deleteService" wire:confirm="{{ __('admin.services.delete_confirm') }}" style="color: var(--error-600); width: 100%;">{{ __('admin.services.delete') }}</x-button>
                </x-admin.card>
            @endif
        </div>
    </div>
</div>

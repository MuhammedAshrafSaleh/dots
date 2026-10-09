@php
    $tabs = ['basic', 'cat', 'var', 'spec', 'img', 'files', 'rel', 'flags', 'seo'];
    $attr = fn ($id) => $axisAttributes->firstWhere('id', $id);
@endphp
<div class="a-page">
    <x-admin.head :title="$product ? $product->getTranslation('name', app()->getLocale(), false) : __('admin.product_edit.new')">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.products') }}" wire:navigate>{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.product_edit.save') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <div class="a-split" style="--a-aside: 300px;">
        <div style="display: grid; gap: 16px; min-width: 0;">
            <div class="a-card" style="padding: 0 18px;">
                <x-tabs
                    :value="$activeTab"
                    :tabs="collect($tabs)->map(fn ($t) => ['id' => $t, 'label' => __('admin.product_edit.tab_'.$t)])->all()"
                    x-on:tab-changed="$wire.set('activeTab', $event.detail)"
                />
            </div>

            {{-- Basic --}}
            <div @class(['a-hidden' => $activeTab !== 'basic'])>
                <x-admin.card :title="__('admin.product_edit.tab_basic')">
                    <div style="display: grid; gap: 18px;">
                        <x-admin.field-bilingual :label="__('admin.product_edit.product_name')" model="name" />
                        @error('name.ar')<span class="d-help d-help--error">{{ $message }}</span>@enderror
                        @error('name.en')<span class="d-help d-help--error">{{ $message }}</span>@enderror
                        <x-admin.field-bilingual :label="__('admin.product_edit.overview')" model="overview" multiline :rows="5" />
                        <x-admin.field-bilingual :label="__('admin.product_edit.variants_summary')" model="variants_summary" />
                        <x-admin.field-bilingual :label="__('admin.product_edit.warranty')" model="warranty" />
                        <div style="max-width: 50%;">
                            <x-input :label="__('admin.product_edit.base_sku')" dir="ltr" wire:model="sku" :error="$errors->first('sku')" />
                        </div>
                    </div>
                </x-admin.card>
            </div>

            {{-- Category & brand --}}
            <div @class(['a-hidden' => $activeTab !== 'cat'])>
                <x-admin.card :title="__('admin.product_edit.tab_cat')">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
                        <x-select :label="__('admin.product_edit.brand')" wire:model="brand_id" :error="$errors->first('brand_id')"
                            :options="$brands->map(fn ($b) => ['value' => $b->id, 'label' => $b->name])->all()" :placeholder="__('admin.product_edit.brand')" />
                        <x-select :label="__('admin.product_edit.category')" wire:model="category_id" :help="__('admin.product_edit.category_help')" :error="$errors->first('category_id')"
                            :options="$leaves->map(fn ($c) => ['value' => $c->id, 'label' => trim(($c->parent?->parent?->getTranslation('name', app()->getLocale(), false) ? $c->parent->parent->getTranslation('name', app()->getLocale(), false).' › ' : '').($c->parent?->getTranslation('name', app()->getLocale(), false) ? $c->parent->getTranslation('name', app()->getLocale(), false).' › ' : '').$c->getTranslation('name', app()->getLocale(), false))])->all()"
                            :placeholder="__('admin.product_edit.category')" />
                    </div>
                </x-admin.card>
            </div>

            {{-- Variants --}}
            <div @class(['a-hidden' => $activeTab !== 'var']) style="display: grid; gap: 16px;">
                <x-admin.card>
                    <x-admin.switch wire:model.live="price_on_request" :label="__('admin.product_edit.price_on_request')" :help="__('admin.product_edit.price_on_request_help')" />
                </x-admin.card>

                <x-admin.card :title="__('admin.product_edit.attribute_axes')">
                    <x-slot:action>
                        <a class="d-btn d-btn--ghost d-btn--sm" href="{{ route('admin.attributes') }}" wire:navigate>{{ __('admin.product_edit.manage_attributes') }} <x-icon name="arrow-right" size="14" /></a>
                    </x-slot:action>
                    <div style="display: grid; gap: 14px;">
                        @forelse ($axes as $i => $axis)
                            @php $a = $attr($axis['attribute_id']); @endphp
                            @if ($a)
                                <div style="display: grid; grid-template-columns: 180px minmax(0,1fr) auto; gap: 10px; align-items: center; padding-bottom: 14px; border-bottom: 1px solid var(--border-1);">
                                    <b style="font: 800 14px var(--font-body);">{{ $a->getTranslation('name', app()->getLocale(), false) }}<span class="a-sub a-mono">{{ $a->code }}</span></b>
                                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                                        @foreach ($a->values as $v)
                                            @php $on = in_array($v->id, $axis['values'], true); @endphp
                                            <button type="button" class="d-chip {{ $on ? 'd-chip--active' : '' }}" aria-pressed="{{ $on ? 'true' : 'false' }}" wire:click="toggleAxisValue({{ $i }}, {{ $v->id }})">
                                                @if ($a->display->value === 'swatch')<span style="width: 14px; height: 14px; border-radius: 50%; background: {{ $v->swatch }}; box-shadow: inset 0 0 0 1px var(--border-2);"></span>@endif
                                                {{ $v->getTranslation('label', app()->getLocale(), false) }}
                                            </button>
                                        @endforeach
                                    </div>
                                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="removeAxis({{ $i }})" aria-label="{{ __('admin.product_edit.remove_axis') }}"><x-icon name="trash-2" size="16" /></button>
                                </div>
                            @endif
                        @empty
                            <span class="d-help">{{ __('admin.product_edit.no_axes') }}</span>
                        @endforelse

                        @php $free = $axisAttributes->reject(fn ($a) => collect($axes)->contains('attribute_id', $a->id)); @endphp
                        @if ($free->isNotEmpty())
                            <div style="max-width: 320px;">
                                <x-select size="sm" wire:model="newAxis" wire:change="addAxis" :placeholder="__('admin.product_edit.add_axis')"
                                    :options="$free->map(fn ($a) => ['value' => $a->id, 'label' => $a->getTranslation('name', app()->getLocale(), false)])->all()" />
                            </div>
                        @endif
                    </div>
                </x-admin.card>

                <x-admin.card :body="false">
                    <x-slot:title>{{ __('admin.product_edit.variants') }} <x-badge tone="neutral">{{ count($variantRows) }}</x-badge></x-slot:title>
                    @if (count($variantRows) > 1 && ! $price_on_request)
                        <x-slot:action><x-button size="sm" variant="outline" wire:click="applyFirstPrice">{{ __('admin.product_edit.apply_first_price') }}</x-button></x-slot:action>
                    @endif

                    <div class="a-scroll">
                        <table class="a-table" style="min-width: 820px;">
                            <thead><tr>
                                <th>{{ __('admin.product_edit.variant') }}</th>
                                <th>{{ __('admin.product_edit.col_sku') }}</th>
                                <th>{{ __('admin.product_edit.col_price') }}</th>
                                <th>{{ __('admin.product_edit.col_compare') }}</th>
                                <th>{{ __('admin.product_edit.col_stock_qty') }}</th>
                                <th>{{ __('admin.product_edit.col_low') }}</th>
                                <th class="is-shrink">{{ __('admin.product_edit.col_default') }}</th>
                                <th class="is-shrink">{{ __('admin.product_edit.col_active') }}</th>
                            </tr></thead>
                            <tbody>
                                @foreach ($variantRows as $key => $row)
                                    <tr wire:key="var-{{ $key }}">
                                        <td>
                                            @if ($row['value_ids'])
                                                <span style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                    @foreach ($row['value_ids'] as $vid)
                                                        @php $label = optional(\App\Models\AttributeValue::find($vid))->getTranslation('label', app()->getLocale(), false); @endphp
                                                        <x-badge tone="outline">{{ $label }}</x-badge>
                                                    @endforeach
                                                </span>
                                            @else
                                                <x-badge tone="neutral">{{ __('admin.product_edit.default_variant') }}</x-badge>
                                            @endif
                                        </td>
                                        <td style="padding: 6px;">
                                            <input class="d-input d-input--sm a-mono" style="min-width: 170px;" dir="ltr" wire:model="variantRows.{{ $key }}.sku" aria-label="SKU">
                                            @error("variantRows.$key.sku")<span class="d-help d-help--error">{{ $message }}</span>@enderror
                                        </td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" type="number" step="0.01" style="width: 100px;" dir="ltr" @disabled($price_on_request) wire:model="variantRows.{{ $key }}.price" aria-label="price"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" type="number" step="0.01" style="width: 100px;" dir="ltr" @disabled($price_on_request) wire:model="variantRows.{{ $key }}.compare_at_price" aria-label="compare"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" type="number" style="width: 80px;" dir="ltr" wire:model="variantRows.{{ $key }}.stock_qty" aria-label="stock"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" type="number" style="width: 90px;" dir="ltr" placeholder="{{ __('admin.product_edit.low_default') }}" wire:model="variantRows.{{ $key }}.low_stock_threshold" aria-label="low"></td>
                                        <td class="is-shrink"><input type="radio" name="ap-default" @checked($defaultKey === $key) wire:click="$set('defaultKey', '{{ $key }}')" style="width: 18px; height: 18px; accent-color: var(--ink-900);" aria-label="{{ __('admin.product_edit.default_variant') }}"></td>
                                        <td class="is-shrink"><x-admin.switch wire:model="variantRows.{{ $key }}.is_active" /></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <x-slot:foot><span class="d-help">{{ __('admin.product_edit.low_help') }}</span></x-slot:foot>
                </x-admin.card>
            </div>

            {{-- Specs --}}
            <div @class(['a-hidden' => $activeTab !== 'spec'])>
                <x-admin.card :title="__('admin.product_edit.specs')">
                    <x-slot:action><span class="a-count">{{ __('admin.product_edit.spec_drag') }}</span></x-slot:action>
                    <div class="a-scroll">
                        <table class="a-table" style="min-width: 680px;">
                            <thead><tr>
                                <th class="is-shrink"></th>
                                <th>{{ __('admin.product_edit.spec_name') }} · EN</th>
                                <th>{{ __('admin.product_edit.spec_name') }} · {{ __('admin.widgets.ar') }}</th>
                                <th>{{ __('admin.product_edit.spec_value') }} · EN</th>
                                <th>{{ __('admin.product_edit.spec_value') }} · {{ __('admin.widgets.ar') }}</th>
                                <th class="is-shrink"></th>
                            </tr></thead>
                            <tbody x-data="sortable({ wire: $wire, method: 'reorderSpecs' })">
                                @foreach ($specs as $key => $spec)
                                    <tr wire:key="spec-{{ $key }}" data-sortable-key="{{ $key }}" draggable="true"
                                        @dragstart="start($event, {{ $loop->index }})" @dragover="over($event)" @drop="drop($event, {{ $loop->index }})">
                                        <td class="is-shrink"><span class="a-grip" style="cursor: grab;"><x-icon name="grip-vertical" size="16" /></span></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="ltr" wire:model="specs.{{ $key }}.name.en" aria-label="Name EN"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="rtl" wire:model="specs.{{ $key }}.name.ar" aria-label="Name AR"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="ltr" wire:model="specs.{{ $key }}.value.en" aria-label="Value EN"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="rtl" wire:model="specs.{{ $key }}.value.ar" aria-label="Value AR"></td>
                                        <td class="is-shrink"><button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="removeSpec('{{ $key }}')" aria-label="{{ __('admin.widgets.remove') }}"><x-icon name="trash-2" size="16" /></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div><x-button size="sm" variant="outline" icon="plus" wire:click="addSpec">{{ __('admin.product_edit.add_row') }}</x-button></div>
                </x-admin.card>
            </div>

            {{-- Images --}}
            <div @class(['a-hidden' => $activeTab !== 'img'])>
                <x-admin.card :title="__('admin.product_edit.images')">
                    <x-slot:action><span class="a-count">{{ __('admin.product_edit.images_hint') }}</span></x-slot:action>
                    <x-admin.gallery model="galleryUploads" :items="$galleryItems" reorder="reorderGallery" remove="removeImage" caption-model="captions" :help="__('admin.product_edit.images_hint')" />
                </x-admin.card>
            </div>

            {{-- Files --}}
            <div @class(['a-hidden' => $activeTab !== 'files'])>
                <x-admin.card :title="__('admin.product_edit.files')">
                    <x-slot:action><span class="a-count">{{ __('admin.product_edit.files_hint') }}</span></x-slot:action>
                    <div class="d-files">
                        @foreach ($fileItems as $file)
                            <div class="d-file" wire:key="file-{{ $file['id'] }}">
                                <span class="d-file__ico"><x-icon name="file-text" size="20" /><span class="d-file__ext">{{ strtoupper(pathinfo($file['name'], PATHINFO_EXTENSION)) }}</span></span>
                                <span style="min-width: 0; flex: 1;"><span class="d-file__name" style="display: block;">{{ $file['name'] }}</span></span>
                                <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="removeFile({{ $file['id'] }})" aria-label="{{ __('admin.widgets.remove') }}"><x-icon name="trash-2" size="16" /></button>
                            </div>
                        @endforeach
                    </div>
                    <div class="d-field">
                        <label class="a-drop" style="min-height: 120px;">
                            <input type="file" multiple wire:model="fileUploads" accept="application/pdf,.ies,.ldt" style="position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0,0,0,0);">
                            <x-icon name="upload" size="22" />{{ __('admin.widgets.upload') }}
                        </label>
                        @error('fileUploads.*')<span class="d-help d-help--error">{{ $message }}</span>@enderror
                    </div>
                </x-admin.card>
            </div>

            {{-- Related --}}
            <div @class(['a-hidden' => $activeTab !== 'rel']) style="display: grid; gap: 16px;">
                <x-admin.card :title="__('admin.product_edit.often_together')">
                    <x-slot:action><span class="a-count">{{ __('admin.product_edit.often_together_hint') }}</span></x-slot:action>
                    <livewire:admin.widgets.product-picker :key="'rel-'.($product?->id ?? 'new')" wire:model="relatedIds" :exclude="$product?->id" />
                </x-admin.card>
                <x-admin.card :title="__('admin.product_edit.cart_suggestions')">
                    <x-slot:action><span class="a-count">{{ __('admin.product_edit.cart_suggestions_hint') }}</span></x-slot:action>
                    <livewire:admin.widgets.product-picker :key="'cart-'.($product?->id ?? 'new')" wire:model="cartSuggestionIds" :exclude="$product?->id" />
                </x-admin.card>
            </div>

            {{-- Flags --}}
            <div @class(['a-hidden' => $activeTab !== 'flags'])>
                <x-admin.card :title="__('admin.product_edit.badges')">
                    <div style="display: grid; gap: 14px;">
                        @foreach ([['is_new', 'new'], ['is_best_seller', 'best'], ['is_smart_pick', 'smart'], ['is_dimmable', 'dim'], ['ships_24h', 'fast']] as [$model, $k])
                            <div style="padding-bottom: 14px; border-bottom: 1px solid var(--border-1);">
                                <x-admin.switch wire:model="{{ $model }}" :label="__('admin.product_edit.flag_'.$k)" :help="__('admin.product_edit.flag_'.$k.'_help')" />
                            </div>
                        @endforeach
                        <div style="max-width: 260px;">
                            <x-input type="number" dir="ltr" :label="__('admin.product_edit.sort_weight')" :help="__('admin.product_edit.sort_weight_help')" wire:model="sort_weight" :error="$errors->first('sort_weight')" />
                        </div>
                    </div>
                </x-admin.card>
            </div>

            {{-- SEO --}}
            <div @class(['a-hidden' => $activeTab !== 'seo'])>
                <x-admin.seo slug-model="slug" title-model="meta_title" desc-model="meta_description" path="/p" />
                @error('slug.ar')<span class="d-help d-help--error">{{ $message }}</span>@enderror
                @error('slug.en')<span class="d-help d-help--error">{{ $message }}</span>@enderror
            </div>
        </div>

        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.product_edit.visibility')">
                <div style="display: grid; gap: 16px;">
                    <x-select :label="__('admin.product_edit.status')" wire:model="status"
                        :options="[['value' => 'draft', 'label' => __('admin.products.status_draft')], ['value' => 'active', 'label' => __('admin.products.status_active')], ['value' => 'archived', 'label' => __('admin.products.status_archived')]]" />
                    @if ($product)
                        <x-image-frame :src="$product->getFirstMediaUrl('gallery')" ratio="1 / 1" style="border-radius: var(--radius-md); border: 1px solid var(--border-1);" />
                    @endif
                </div>
            </x-admin.card>
            @if ($product)
                @can('products.delete')
                    <x-button variant="ghost" icon="trash-2" style="color: var(--error-600); justify-self: start;"
                        wire:click="deleteProduct" wire:confirm="{{ __('admin.product_edit.delete_confirm') }}">
                        {{ __('admin.product_edit.delete') }}
                    </x-button>
                @endcan
            @endif
        </div>
    </div>
</div>

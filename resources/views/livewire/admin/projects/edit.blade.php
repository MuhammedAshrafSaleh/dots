@php
    $tabs = ['basic', 'details', 'content', 'links', 'gallery', 'seo'];
    $statusOptions = ['current' => __('admin.projects.status_current'), 'completed' => __('admin.projects.status_completed')];
    $typeOptions = [
        'residential' => __('admin.projects.type_residential'),
        'commercial'  => __('admin.projects.type_commercial'),
        'outdoor'     => __('admin.projects.type_outdoor'),
        'smart_home'  => __('admin.projects.type_smart_home'),
        'hospitality' => __('admin.projects.type_hospitality'),
    ];
    $stageOptions = [
        'design'         => __('admin.projects.stage_design'),
        'first_fix'      => __('admin.projects.stage_first_fix'),
        'installation'   => __('admin.projects.stage_installation'),
        'commissioning'  => __('admin.projects.stage_commissioning'),
        'handover'       => __('admin.projects.stage_handover'),
    ];
@endphp
<div class="a-page">
    <x-admin.head :title="$project ? $project->getTranslation('title', app()->getLocale(), false) : __('admin.projects.new')">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.projects') }}" wire:navigate>{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.projects.save') }}</x-button>
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
                        {{ __('admin.projects.tab_'.$t) }}@if ($tabHasError)<span aria-hidden="true" style="color: var(--error-600); margin-inline-start: 4px;">●</span>@endif
                    </button>
                @endforeach
            </div>

            {{-- Basic --}}
            <div @class(['a-hidden' => $activeTab !== 'basic'])>
                <x-admin.card :title="__('admin.projects.tab_basic')">
                    <div style="display: grid; gap: 18px;">
                        <x-admin.field-bilingual :label="__('admin.projects.title_field')" model="title" required />

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

                        <x-select :label="__('admin.projects.type')" wire:model="type" :options="$typeOptions" :selected="$type" :error="$errors->first('type')" />

                        <x-admin.field-bilingual :label="__('admin.projects.location')" model="location" />
                        <x-admin.field-bilingual :label="__('admin.projects.client')" model="client" :help="__('admin.projects.client_hint')" />
                    </div>
                </x-admin.card>
            </div>

            {{-- Details --}}
            <div @class(['a-hidden' => $activeTab !== 'details'])>
                <x-admin.card :title="__('admin.projects.tab_details')">
                    <div style="display: grid; gap: 18px;">
                        <x-select :label="__('admin.projects.status')" wire:model="status" :options="$statusOptions" :selected="$status" :error="$errors->first('status')" />

                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <x-input type="date" dir="ltr" :label="__('admin.projects.start_date')" wire:model="start_date" :error="$errors->first('start_date')" />
                            <x-input type="date" dir="ltr" :label="__('admin.projects.expected_completion')" wire:model="expected_completion" :error="$errors->first('expected_completion')" />
                        </div>

                        <x-input type="number" dir="ltr" :label="__('admin.projects.completed_year')" wire:model="completed_year" :error="$errors->first('completed_year')" placeholder="2024" />

                        <x-select :label="__('admin.projects.stage')" wire:model="stage" :options="$stageOptions" :selected="$stage" :error="$errors->first('stage')" />

                        <x-input type="number" dir="ltr" :label="__('admin.projects.progress')" wire:model="progress" min="0" max="100" :error="$errors->first('progress')" />

                        <x-admin.field-bilingual :label="__('admin.projects.phase_label')" model="phase_label" />

                        <x-admin.field-bilingual :label="__('admin.projects.scope_summary')" model="scope_summary" multiline :rows="3" />
                    </div>
                </x-admin.card>
            </div>

            {{-- Content --}}
            <div @class(['a-hidden' => $activeTab !== 'content'])>
                <x-admin.card :title="__('admin.projects.tab_content')">
                    <div style="display: grid; gap: 18px;">
                        <x-admin.rich :label="__('admin.projects.about')" model="about" :min-h="280" />
                        <x-admin.field-bilingual :label="__('admin.projects.challenge')" model="challenge" multiline :rows="4" />
                        <x-admin.field-bilingual :label="__('admin.projects.solution')" model="solution" multiline :rows="4" />
                        <x-admin.field-bilingual :label="__('admin.projects.result')" model="result" multiline :rows="4" />
                    </div>
                </x-admin.card>
            </div>

            {{-- Links (products + services) --}}
            <div @class(['a-hidden' => $activeTab !== 'links'])>
                {{-- Products --}}
                <x-admin.card :title="__('admin.projects.products')" style="margin-bottom: 16px;">
                    @if ($pickedProducts->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                            @foreach ($pickedProducts as $prod)
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: var(--surface-2); border: 1px solid var(--border-1); border-radius: 6px; padding: 4px 10px; font-size: 13px;">
                                    {{ $prod->getTranslation('name', app()->getLocale(), false) ?: $prod->getTranslation('name', 'en', false) }}
                                    <button type="button" wire:click="removeProduct({{ $prod->id }})" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" style="width: 18px; height: 18px;" aria-label="{{ __('admin.widgets.remove') }}"><x-icon name="x" size="14" /></button>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="a-sub" style="margin-bottom: 12px;">{{ __('admin.projects.no_products') }}</p>
                    @endif

                    <div style="position: relative;">
                        <x-input size="sm" icon="search" :placeholder="__('admin.projects.products_search')" wire:model.live.debounce.300ms="productSearch" />
                        @if ($productResults->isNotEmpty())
                            <div style="position: absolute; z-index: 10; top: 100%; inset-inline-start: 0; inset-inline-end: 0; background: var(--surface-1); border: 1px solid var(--border-1); border-radius: 8px; box-shadow: var(--shadow-2); margin-top: 4px; overflow: hidden;">
                                @foreach ($productResults as $prod)
                                    <button type="button" wire:click="addProduct({{ $prod->id }})"
                                        style="display: flex; width: 100%; padding: 10px 14px; gap: 8px; align-items: center; background: none; border: none; cursor: pointer; font-size: 14px; text-align: start;"
                                        onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'">
                                        <x-icon name="plus" size="14" style="color: var(--ink-400); flex: none;" />
                                        {{ $prod->getTranslation('name', app()->getLocale(), false) ?: $prod->getTranslation('name', 'en', false) }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-admin.card>

                {{-- Services --}}
                <x-admin.card :title="__('admin.projects.services')">
                    @if ($pickedServices->isNotEmpty())
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                            @foreach ($pickedServices as $svc)
                                <span style="display: inline-flex; align-items: center; gap: 6px; background: var(--surface-2); border: 1px solid var(--border-1); border-radius: 6px; padding: 4px 10px; font-size: 13px;">
                                    {{ $svc->getTranslation('title', app()->getLocale(), false) ?: $svc->getTranslation('title', 'en', false) }}
                                    <button type="button" wire:click="removeService({{ $svc->id }})" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" style="width: 18px; height: 18px;" aria-label="{{ __('admin.widgets.remove') }}"><x-icon name="x" size="14" /></button>
                                </span>
                            @endforeach
                        </div>
                    @else
                        <p class="a-sub" style="margin-bottom: 12px;">{{ __('admin.projects.no_services') }}</p>
                    @endif

                    <div style="position: relative;">
                        <x-input size="sm" icon="search" :placeholder="__('admin.projects.services_search')" wire:model.live.debounce.300ms="serviceSearch" />
                        @if ($serviceResults->isNotEmpty())
                            <div style="position: absolute; z-index: 10; top: 100%; inset-inline-start: 0; inset-inline-end: 0; background: var(--surface-1); border: 1px solid var(--border-1); border-radius: 8px; box-shadow: var(--shadow-2); margin-top: 4px; overflow: hidden;">
                                @foreach ($serviceResults as $svc)
                                    <button type="button" wire:click="addService({{ $svc->id }})"
                                        style="display: flex; width: 100%; padding: 10px 14px; gap: 8px; align-items: center; background: none; border: none; cursor: pointer; font-size: 14px; text-align: start;"
                                        onmouseover="this.style.background='var(--surface-2)'" onmouseout="this.style.background='none'">
                                        <x-icon name="plus" size="14" style="color: var(--ink-400); flex: none;" />
                                        {{ $svc->getTranslation('title', app()->getLocale(), false) ?: $svc->getTranslation('title', 'en', false) }}
                                    </button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </x-admin.card>
            </div>

            {{-- Gallery --}}
            <div @class(['a-hidden' => $activeTab !== 'gallery'])>
                <x-admin.card :title="__('admin.projects.tab_gallery')">
                    <x-admin.gallery model="galleryUploads" :items="$galleryItems" reorder="reorderGallery" remove="removeGalleryImage" :help="__('admin.projects.gallery_hint')" />
                </x-admin.card>
            </div>

            {{-- SEO --}}
            <div @class(['a-hidden' => $activeTab !== 'seo'])>
                <x-admin.seo title-model="meta_title" desc-model="meta_description" />
            </div>
        </div>

        {{-- Sidebar --}}
        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.projects.visibility')">
                <div style="display: grid; gap: 16px;">
                    <x-admin.switch wire:model="is_active" :label="__('admin.projects.active')" />
                    <x-admin.switch wire:model="is_featured" :label="__('admin.projects.featured')" />
                    <x-input type="number" dir="ltr" :label="__('admin.projects.sort_order')" wire:model="sort_order" :error="$errors->first('sort_order')" />
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.projects.cover_image')">
                <x-admin.image :url="$imageUrl" model="imageUpload" remove="removeProjectImage" ratio="4 / 3" />
            </x-admin.card>

            @if ($project)
                <x-admin.card>
                    <x-button variant="ghost" size="sm" icon="trash-2" wire:click="deleteProject" wire:confirm="{{ __('admin.projects.delete_confirm') }}" style="color: var(--error-600); width: 100%;">{{ __('admin.projects.delete') }}</x-button>
                </x-admin.card>
            @endif
        </div>
    </div>
</div>

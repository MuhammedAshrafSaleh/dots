@php
    $tabs = ['basic', 'body', 'seo'];
@endphp
<div class="a-page">
    <x-admin.head :title="$page ? $page->getTranslation('title', app()->getLocale(), false) : __('admin.content_pages.new')">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.content-pages') }}" wire:navigate>{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.content_pages.save') }}</x-button>
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
                        {{ __('admin.content_pages.tab_'.$t) }}@if ($tabHasError)<span aria-hidden="true" style="color: var(--error-600); margin-inline-start: 4px;">●</span>@endif
                    </button>
                @endforeach
            </div>

            {{-- Basic --}}
            <div @class(['a-hidden' => $activeTab !== 'basic'])>
                <x-admin.card :title="__('admin.content_pages.tab_basic')">
                    <div style="display: grid; gap: 18px;">
                        <x-input
                            :label="__('admin.content_pages.key')"
                            wire:model="key"
                            dir="ltr"
                            :readonly="(bool) $page"
                            :error="$errors->first('key')"
                            :help="__('admin.content_pages.key_help')"
                        />

                        <x-admin.field-bilingual :label="__('admin.content_pages.title_field')" model="title" required />

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

                        <x-admin.field-bilingual :label="__('admin.content_pages.lede')" model="lede" multiline :rows="2" />
                    </div>
                </x-admin.card>
            </div>

            {{-- Body --}}
            <div @class(['a-hidden' => $activeTab !== 'body'])>
                <x-admin.card :title="__('admin.content_pages.tab_body')">
                    <x-admin.rich :label="__('admin.content_pages.body')" model="body" :min-h="360" />
                </x-admin.card>
            </div>

            {{-- SEO --}}
            <div @class(['a-hidden' => $activeTab !== 'seo'])>
                <x-admin.seo title-model="meta_title" desc-model="meta_description" />
            </div>
        </div>

        {{-- Sidebar --}}
        <div style="display: grid; gap: 16px; align-content: start;">
            <x-admin.card :title="__('admin.content_pages.visibility')">
                <div style="display: grid; gap: 16px;">
                    <x-admin.switch wire:model="is_active" :label="__('admin.content_pages.active')" />
                    <x-admin.switch wire:model="show_in_footer" :label="__('admin.content_pages.show_in_footer')" />
                    <x-input type="number" dir="ltr" :label="__('admin.content_pages.sort_order')" wire:model="sort_order" :error="$errors->first('sort_order')" />
                </div>
            </x-admin.card>

            @if ($page)
                <x-admin.card>
                    <div style="display: grid; gap: 8px;">
                        <span class="a-sub" style="font-size: 12px;">{{ __('admin.content_pages.last_updated') }}</span>
                        <span style="font-size: 13px;">{{ $page->updated_at->format('d M Y, H:i') }}</span>
                    </div>
                </x-admin.card>

                <x-admin.card>
                    <x-button variant="ghost" size="sm" icon="trash-2" wire:click="deletePage" wire:confirm="{{ __('admin.content_pages.delete_confirm') }}" style="color: var(--error-600); width: 100%;">{{ __('admin.content_pages.delete') }}</x-button>
                </x-admin.card>
            @endif
        </div>
    </div>
</div>

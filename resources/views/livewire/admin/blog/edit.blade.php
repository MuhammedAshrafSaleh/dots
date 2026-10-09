@php
    $tabs = ['post', 'body', 'products', 'seo'];
    $statusOptions = [
        'draft' => __('admin.blog.status_draft'),
        'published' => __('admin.blog.status_published'),
        'scheduled' => __('admin.blog.status_scheduled'),
    ];
@endphp
<div class="a-page">
    <x-admin.head :title="$post ? $post->getTranslation('title', app()->getLocale(), false) : __('admin.blog.new')">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.blog') }}" wire:navigate>{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">
                {{ $status === 'scheduled' ? __('admin.blog.schedule') : __('admin.blog.save') }}
            </x-button>
        </x-slot:actions>
    </x-admin.head>

    <div class="a-split" style="--a-aside: 280px;">
        {{-- Main column --}}
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
                        {{ __('admin.blog.tab_'.$t) }}@if ($tabHasError)<span aria-hidden="true" style="color: var(--error-600); margin-inline-start: 4px;">●</span>@endif
                    </button>
                @endforeach
            </div>

            {{-- Post tab --}}
            <div @class(['a-hidden' => $activeTab !== 'post'])>
                <x-admin.card :title="__('admin.blog.tab_post')">
                    <div style="display: grid; gap: 18px;">
                        <x-admin.field-bilingual :label="__('admin.blog.title_field')" model="title" required />

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

                        <x-admin.field-bilingual :label="__('admin.blog.excerpt')" model="excerpt" multiline :rows="3" :counter="180" />

                        <x-admin.card :title="__('admin.blog.cover_image')" style="margin: 0;">
                            <x-admin.image :url="$coverUrl" model="imageUpload" remove="removeCoverImage" ratio="16 / 9" />
                        </x-admin.card>
                    </div>
                </x-admin.card>
            </div>

            {{-- Body tab --}}
            <div @class(['a-hidden' => $activeTab !== 'body'])>
                <x-admin.card :title="__('admin.blog.tab_body')">
                    <x-admin.rich model="body" :min-h="320" />
                </x-admin.card>
            </div>

            {{-- Products tab --}}
            <div @class(['a-hidden' => $activeTab !== 'products'])>
                <x-admin.card :title="__('admin.blog.tab_products')">
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
                        <p class="a-sub" style="margin-bottom: 12px;">{{ __('admin.blog.no_products') }}</p>
                    @endif

                    <div style="position: relative;">
                        <x-input size="sm" icon="search" :placeholder="__('admin.blog.products_search')" wire:model.live.debounce.300ms="productSearch" />
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
            </div>

            {{-- SEO tab --}}
            <div @class(['a-hidden' => $activeTab !== 'seo'])>
                <x-admin.seo title-model="meta_title" desc-model="meta_description" />
            </div>
        </div>

        {{-- Sidebar --}}
        <div style="display: grid; gap: 16px; align-content: start;">

            @if ($errors->any())
                <div role="alert" style="background: var(--error-50); border: 1px solid var(--error-300); border-radius: var(--radius-md); padding: 12px 14px; display: grid; gap: 4px;">
                    <b style="font-size: 13px; color: var(--error-700);">{{ __('admin.blog.has_errors') }}</b>
                    <ul style="margin: 4px 0 0; padding-inline-start: 16px; font-size: 12px; color: var(--error-700);">
                        @foreach ($errors->all() as $msg)
                            <li>{{ $msg }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Publishing --}}
            <x-admin.card :title="__('admin.blog.publishing')">
                <div style="display: grid; gap: 16px;">
                    <x-select :label="__('admin.blog.status')" wire:model.live="status" :options="$statusOptions" :selected="$status" :error="$errors->first('status')" />
                    <x-input type="datetime-local" dir="ltr" :label="__('admin.blog.publish_date')" wire:model="published_at"
                        :error="$errors->first('published_at')"
                        :help="$status === 'scheduled' ? __('admin.blog.scheduled_hint') : null" />
                    <x-admin.switch wire:model="is_featured" :label="__('admin.blog.featured')" :help="__('admin.blog.featured_hint')" />
                </div>
            </x-admin.card>

            {{-- Organise --}}
            <x-admin.card :title="__('admin.blog.organise')">
                <div style="display: grid; gap: 16px;">
                    @php $catOptions = $categories->mapWithKeys(fn ($c) => [(string) $c->id => $c->getTranslation('name', app()->getLocale(), false)])->all(); @endphp
                    <x-select :label="__('admin.blog.category')" wire:model="post_category_id" :options="$catOptions" :selected="(string) $post_category_id" placeholder="{{ __('admin.blog.category_placeholder') }}" :error="$errors->first('post_category_id')" />

                    <div class="d-field">
                        <span class="d-label">{{ __('admin.blog.tags') }}</span>
                        <div style="display: flex; flex-wrap: wrap; gap: 6px;">
                            @foreach ($allTags as $tag)
                                @php $isSelected = in_array($tag->id, $selectedTagIds, true); @endphp
                                <button type="button"
                                    wire:click="toggleChip('selectedTagIds', {{ $tag->id }})"
                                    class="d-btn d-btn--sm {{ $isSelected ? 'd-btn--primary' : 'd-btn--outline' }}"
                                    style="font-size: 12px; padding: 2px 10px;">
                                    {{ $tag->getTranslation('name', app()->getLocale(), false) ?: $tag->slug }}
                                </button>
                            @endforeach
                            @if ($allTags->isEmpty())
                                <span class="a-sub">{{ __('admin.blog.no_tags_yet') }}</span>
                            @endif
                        </div>
                    </div>

                    <x-input type="number" dir="ltr" :label="__('admin.blog.read_minutes')" wire:model="read_minutes" min="1" max="120" :error="$errors->first('read_minutes')" />
                </div>
            </x-admin.card>

            {{-- Author --}}
            <x-admin.card :title="__('admin.blog.author')" :action="__('admin.widgets.optional')">
                <div style="display: grid; gap: 16px;">
                    <x-admin.field-bilingual :label="__('admin.blog.author_name')" model="author_name" />
                    <x-admin.field-bilingual :label="__('admin.blog.author_role')" model="author_role" />
                    <x-admin.image :url="$authorPhotoUrl" model="authorPhotoUpload" remove="removeAuthorPhotoAction" ratio="1 / 1" />
                </div>
            </x-admin.card>

            @if ($post)
                <x-admin.card>
                    <x-button variant="ghost" size="sm" icon="trash-2" wire:click="deletePost" wire:confirm="{{ __('admin.blog.delete_confirm') }}" style="color: var(--error-600); width: 100%;">{{ __('admin.blog.delete') }}</x-button>
                </x-admin.card>
            @endif
        </div>
    </div>
</div>

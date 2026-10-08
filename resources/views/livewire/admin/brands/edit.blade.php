@php
    $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'dots.eg';
    $mediaUrl = function (string $collection, $upload) {
        if ($upload) {
            return str_contains($collection, 'catalogue') ? null : $upload->temporaryUrl();
        }
        if (($this->remove[$collection] ?? false)) {
            return null;
        }
        $url = $this->brand?->getFirstMediaUrl($collection);
        return $url !== '' ? $url : null;
    };
    $catalogueName = $catalogueUpload
        ? $catalogueUpload->getClientOriginalName()
        : (! ($remove['catalogue'] ?? false) && $brand && $brand->getFirstMedia('catalogue') ? $brand->getFirstMedia('catalogue')->file_name : null);
@endphp
<div class="a-page">
    <x-admin.head :title="$brand ? $brand->name : __('admin.brands.new')">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.brands') }}" wire:navigate>{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.brands.save') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <div class="a-split" style="--a-aside: 320px;">
        <div style="display: grid; gap: 16px; min-width: 0;">
            <x-admin.card :title="__('admin.brands.basic_info')">
                <div style="display: grid; gap: 18px;">
                    <x-input :label="__('admin.brands.name')" wire:model="name" :error="$errors->first('name')" />

                    <div class="d-field">
                        <span class="d-label" style="display: flex; justify-content: space-between; gap: 8px;">
                            {{ __('admin.widgets.slug') }}
                            <button type="button" class="a-link" wire:click="generateSlug" style="font-size: 12px;"><x-icon name="refresh-cw" size="13" /> {{ __('admin.widgets.auto_generate') }}</button>
                        </span>
                        <x-input dir="ltr" wire:model="slug" :error="$errors->first('slug')">
                            <x-slot:help><span dir="ltr">{{ $host }}/{{ app()->getLocale() }}/brands/<span>{{ $slug }}</span></span></x-slot:help>
                        </x-input>
                    </div>

                    <x-admin.field-bilingual :label="__('admin.brands.origin_country')" model="origin_country" />
                    <x-admin.field-bilingual :label="__('admin.brands.description')" model="description" multiline :rows="4" />
                    <x-admin.field-bilingual :label="__('admin.brands.feature_text')" model="feature_text" multiline :rows="2" />
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.brands.media')">
                <div style="display: grid; gap: 18px;">
                    <x-admin.image :label="__('admin.brands.cover_image')" :url="$mediaUrl('cover', $coverUpload)" model="coverUpload" remove="removeMedia('cover')" ratio="16 / 6" />
                    <x-admin.image :label="__('admin.brands.feature_image')" :url="$mediaUrl('feature', $featureUpload)" model="featureUpload" remove="removeMedia('feature')" ratio="4 / 3" />
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.brands.catalogue')">
                <div style="display: grid; gap: 18px;">
                    <x-admin.file :label="__('admin.brands.catalogue_pdf')" model="catalogueUpload" :name="$catalogueName" remove="removeMedia('catalogue')" />
                    <x-admin.field-bilingual :label="__('admin.brands.catalogue_label')" model="catalogue_label" />
                </div>
            </x-admin.card>

            <x-admin.seo title-model="meta_title" desc-model="meta_description" />
        </div>

        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.brands.visibility')">
                <div style="display: grid; gap: 16px;">
                    <x-admin.switch wire:model="is_active" :label="__('admin.brands.active')" />
                    <x-admin.switch wire:model="is_featured" :label="__('admin.brands.featured')" :help="__('admin.brands.featured_help')" />
                    <x-admin.switch wire:model="is_authorised" :label="__('admin.brands.authorised_badge')" />
                    <x-admin.switch wire:model="show_in_partners" :label="__('admin.brands.show_partners')" />
                    <x-admin.switch wire:model="show_on_home" :label="__('admin.brands.show_home')" />
                    <x-input type="number" dir="ltr" :label="__('admin.brands.sort_order')" wire:model="sort_order" :error="$errors->first('sort_order')" />
                </div>
            </x-admin.card>

            <x-admin.card :title="__('admin.brands.logo')">
                <x-admin.image :url="$mediaUrl('logo', $logoUpload)" model="logoUpload" remove="removeMedia('logo')" ratio="3 / 2" contain />
            </x-admin.card>
        </div>
    </div>
</div>

@props([
    'titleModel' => 'meta_title',
    'descModel' => 'meta_description',
    'slugModel' => null,     // pass a base path to include the per-locale slug fields
    'path' => '',
    'generate' => null,
])
{{-- Mirror of admin-kit.jsx ASeo: slugs + meta title/description + Google preview. --}}
<x-admin.card :title="__('admin.widgets.seo')" icon="search">
    <div style="display: grid; gap: 18px;">
        @if ($slugModel)
            <x-admin.slugs :model="$slugModel" :path="$path" :generate="$generate" />
        @endif

        <x-admin.field-bilingual :label="__('admin.widgets.meta_title')" :model="$titleModel" :counter="60" />
        <x-admin.field-bilingual :label="__('admin.widgets.meta_description')" :model="$descModel" multiline :rows="3" :counter="160" />

        <div x-data="{ loc: @js(app()->getLocale()) }"
             style="border: 1px solid var(--border-1); border-radius: var(--radius-md); padding: 14px; display: grid; gap: 4px; background: var(--ink-50);">
            <span class="a-count">{{ __('admin.widgets.google_preview') }}</span>
            <b style="font: 600 17px/1.3 var(--font-body); color: var(--info-600);"
               x-text="$wire.get('{{ $titleModel }}.' + loc) || @js(__('admin.widgets.page_title'))"></b>
            <span style="font: 500 13px/1.5 var(--font-body); color: var(--fg-2);"
                  x-text="$wire.get('{{ $descModel }}.' + loc) || @js(__('admin.widgets.page_description'))"></span>
        </div>
    </div>
</x-admin.card>

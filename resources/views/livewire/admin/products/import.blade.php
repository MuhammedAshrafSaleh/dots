<div class="a-page">
    <x-admin.head
        :title="__('admin.import_products.title')"
        :sub="__('admin.import_products.subtitle')"
    >
        <x-slot:crumbs>
            @if (\Illuminate\Support\Facades\Route::has('admin.products'))
                <x-breadcrumbs :items="[
                    ['label' => __('admin.nav.products'), 'href' => route('admin.products')],
                    ['label' => __('admin.import_products.title')],
                ]" />
            @endif
        </x-slot:crumbs>
    </x-admin.head>

    <div style="display: grid; gap: 20px; max-width: 720px;">

        {{-- Template & Export --}}
        <x-admin.card :title="__('admin.import_products.card_files')">
            <p style="margin-bottom: 16px;">{{ __('admin.import_products.card_files_desc') }}</p>
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                @can('products.import')
                    <x-button
                        icon="download"
                        wire:click="downloadTemplate"
                        wire:loading.attr="disabled"
                    >
                        {{ __('admin.import_products.download_template') }}
                    </x-button>
                @endcan

                @can('products.export')
                    <x-button
                        variant="outline"
                        icon="database"
                        wire:click="export"
                        wire:loading.attr="disabled"
                    >
                        {{ __('admin.import_products.export') }}
                    </x-button>
                @endcan
            </div>
        </x-admin.card>

        {{-- Upload (BI.2/BI.3 will wire this up) --}}
        <x-admin.card :title="__('admin.import_products.card_upload')">
            <x-admin.alert tone="info">
                {{ __('admin.import_products.upload_coming') }}
            </x-admin.alert>
        </x-admin.card>

    </div>
</div>

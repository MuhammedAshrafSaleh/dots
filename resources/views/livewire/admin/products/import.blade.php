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

    <div style="display: grid; gap: 20px; max-width: 820px;">

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

        {{-- Upload --}}
        @can('products.import')
        <x-admin.card :title="__('admin.import_products.card_upload')">
            @if ($showPreview)
                {{-- Preview panel --}}
                <div style="display: grid; gap: 16px;">

                    {{-- Counts summary --}}
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        @foreach ([
                            ['label' => __('admin.import_products.stat_create'),   'value' => $counts['create'],   'bg' => '#d4edda'],
                            ['label' => __('admin.import_products.stat_update'),   'value' => $counts['update'],   'bg' => '#d1ecf1'],
                            ['label' => __('admin.import_products.stat_variants'), 'value' => $counts['variants'], 'bg' => '#f8f9fa'],
                            ['label' => __('admin.import_products.stat_specs'),    'value' => $counts['specs'],    'bg' => '#f8f9fa'],
                            ['label' => __('admin.import_products.stat_images'),   'value' => $counts['images'],   'bg' => '#f8f9fa'],
                        ] as $stat)
                            <div style="background: {{ $stat['bg'] }}; border: 1px solid #dee2e6; border-radius: 6px; padding: 10px 16px; min-width: 100px; text-align: center;">
                                <div style="font-size: 22px; font-weight: 700;">{{ $stat['value'] }}</div>
                                <div style="font-size: 12px; color: #555; margin-top: 2px;">{{ $stat['label'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Errors table --}}
                    @if (count($parseErrors) > 0)
                        <div>
                            <p style="font-weight: 600; color: var(--color-danger, #c00); margin-bottom: 8px;">
                                {{ __('admin.import_products.errors_title', ['count' => count($parseErrors)]) }}
                            </p>
                            <div style="overflow-x: auto;">
                                <table class="a-table" style="font-size: 13px; min-width: 560px;">
                                    <thead>
                                        <tr>
                                            <th>{{ __('admin.import_products.col_sheet') }}</th>
                                            <th>{{ __('admin.import_products.col_row') }}</th>
                                            <th>{{ __('admin.import_products.col_column') }}</th>
                                            <th>{{ __('admin.import_products.col_message') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($parseErrors as $err)
                                            <tr>
                                                <td>{{ $err['sheet'] }}</td>
                                                <td>{{ $err['row'] }}</td>
                                                <td>{{ $err['column'] }}</td>
                                                <td>{{ $err['message'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    {{-- Warnings table --}}
                    @if (count($parseWarnings) > 0)
                        <div>
                            <p style="font-weight: 600; color: var(--color-warning, #8a6600); margin-bottom: 8px;">
                                {{ __('admin.import_products.warnings_title', ['count' => count($parseWarnings)]) }}
                            </p>
                            <div style="overflow-x: auto;">
                                <table class="a-table" style="font-size: 13px; min-width: 560px;">
                                    <thead>
                                        <tr>
                                            <th>{{ __('admin.import_products.col_sheet') }}</th>
                                            <th>{{ __('admin.import_products.col_row') }}</th>
                                            <th>{{ __('admin.import_products.col_column') }}</th>
                                            <th>{{ __('admin.import_products.col_message') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($parseWarnings as $warn)
                                            <tr>
                                                <td>{{ $warn['sheet'] }}</td>
                                                <td>{{ $warn['row'] }}</td>
                                                <td>{{ $warn['column'] }}</td>
                                                <td>{{ $warn['message'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    @if (count($parseErrors) === 0 && count($parseWarnings) === 0)
                        <x-admin.alert tone="success">{{ __('admin.import_products.no_issues') }}</x-admin.alert>
                    @endif

                    {{-- Action buttons --}}
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-top: 4px;">
                        {{-- Apply (wired in BI.3) --}}
                        @if ($hasValidRows)
                            <x-button
                                variant="primary"
                                icon="check"
                                disabled
                                title="{{ __('admin.import_products.apply_coming') }}"
                            >
                                {{ __('admin.import_products.apply') }}
                            </x-button>
                        @endif

                        <x-button
                            variant="outline"
                            icon="download"
                            wire:click="downloadReport"
                            wire:loading.attr="disabled"
                        >
                            {{ __('admin.import_products.download_report') }}
                        </x-button>

                        <x-button
                            variant="ghost"
                            wire:click="cancel"
                            wire:loading.attr="disabled"
                        >
                            {{ __('admin.import_products.cancel') }}
                        </x-button>
                    </div>
                </div>
            @else
                {{-- Upload form --}}
                <div style="display: grid; gap: 16px; max-width: 480px;">
                    <p>{{ __('admin.import_products.upload_desc') }}</p>

                    <div>
                        <label style="display: block; font-weight: 600; margin-bottom: 6px;">
                            {{ __('admin.import_products.label_xlsx') }}
                        </label>
                        <input
                            type="file"
                            accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            wire:model="xlsxFile"
                            style="display: block; width: 100%;"
                        >
                        @error('xlsxFile')
                            <p style="color: var(--color-danger, #c00); font-size: 13px; margin-top: 4px;">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-button
                            icon="search"
                            wire:click="preview"
                            wire:loading.attr="disabled"
                            :disabled="!$xlsxFile"
                        >
                            <span wire:loading.remove wire:target="preview">{{ __('admin.import_products.btn_preview') }}</span>
                            <span wire:loading wire:target="preview">{{ __('admin.import_products.btn_previewing') }}</span>
                        </x-button>
                    </div>
                </div>
            @endif
        </x-admin.card>
        @endcan

    </div>
</div>

@php
    $types = [
        'chip' => ['tag', __('admin.attributes.display_chip')],
        'swatch' => ['palette', __('admin.attributes.display_swatch')],
        'button' => ['rectangle-horizontal', __('admin.attributes.display_button')],
    ];
    $title = $attribute ? $attribute->getTranslation('name', app()->getLocale(), false) : __('admin.attributes.new');
@endphp
<div class="a-page">
    <x-admin.head :title="$title">
        <x-slot:actions>
            <x-button variant="ghost" href="{{ route('admin.attributes') }}" wire:navigate>{{ __('admin.widgets.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.attributes.save') }}</x-button>
        </x-slot:actions>
    </x-admin.head>

    <div class="a-split" style="--a-aside: 320px;">
        <div style="display: grid; gap: 16px; min-width: 0;">
            <x-admin.card :title="__('admin.attributes.title')">
                <div style="display: grid; gap: 18px;">
                    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px;">
                        <x-input :label="__('admin.attributes.code')" dir="ltr" wire:model="code" :help="__('admin.attributes.code_help')" :error="$errors->first('code')" />
                        <x-input :label="__('admin.attributes.unit')" optional dir="ltr" placeholder="K, W, mm" wire:model="unit" :error="$errors->first('unit')" />
                    </div>

                    <x-admin.field-bilingual :label="__('admin.attributes.name')" model="name" />
                    @error('name.ar')<span class="d-help d-help--error">{{ $message }}</span>@enderror
                    @error('name.en')<span class="d-help d-help--error">{{ $message }}</span>@enderror

                    <div class="d-field">
                        <span class="d-label">{{ __('admin.attributes.display_type') }}</span>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                            @foreach ($types as $key => [$icon, $label])
                                <x-radio-card name="at-type" :value="$key" :icon="$icon" :title="$label" :checked="$display === $key" wire:model.live="display" />
                            @endforeach
                        </div>
                    </div>
                </div>
            </x-admin.card>

            <x-admin.card :body="false">
                <x-slot:title>{{ __('admin.attributes.values') }} <x-badge tone="neutral">{{ count($values) }}</x-badge></x-slot:title>
                <x-slot:action><span class="a-count">{{ __('admin.attributes.values_hint') }}</span></x-slot:action>

                @if (count($values) === 0)
                    <x-admin.empty icon="list-plus" :title="__('admin.attributes.no_values_title')" :body="__('admin.attributes.no_values_body')" />
                @else
                    <div class="a-scroll" x-data="sortable({ wire: $wire, method: 'reorderValues' })">
                        <table class="a-table" style="min-width: {{ $display === 'swatch' ? 760 : 560 }}px;">
                            <thead><tr>
                                <th class="is-shrink"></th>
                                <th>{{ __('admin.attributes.value') }}</th>
                                <th>{{ __('admin.attributes.label') }} · EN</th>
                                <th>{{ __('admin.attributes.label') }} · {{ __('admin.widgets.ar') }}</th>
                                @if ($display === 'swatch')<th>{{ __('admin.attributes.swatch') }}</th>@endif
                                <th class="is-shrink"></th>
                            </tr></thead>
                            <tbody>
                                @foreach ($values as $key => $row)
                                    <tr wire:key="val-{{ $key }}" data-sortable-key="{{ $key }}" draggable="true"
                                        @dragstart="start($event, {{ $loop->index }})" @dragover="over($event)" @drop="drop($event, {{ $loop->index }})">
                                        <td class="is-shrink"><span class="a-grip" style="cursor: grab;"><x-icon name="grip-vertical" size="16" /></span></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm a-mono" style="width: 110px;" dir="ltr" wire:model="values.{{ $key }}.value" aria-label="{{ __('admin.attributes.value') }}"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="ltr" wire:model="values.{{ $key }}.label.en" aria-label="Label EN"></td>
                                        <td style="padding: 6px;"><input class="d-input d-input--sm" dir="rtl" wire:model="values.{{ $key }}.label.ar" aria-label="Label AR"></td>
                                        @if ($display === 'swatch')
                                            <td style="padding: 6px;">
                                                <span style="display: flex; gap: 8px; align-items: center;">
                                                    <span style="width: 28px; height: 28px; border-radius: 50%; background: {{ $row['swatch'] }}; box-shadow: inset 0 0 0 1px var(--border-2); flex: none;"></span>
                                                    <select class="d-input d-input--sm" style="width: 180px;" wire:model.live="values.{{ $key }}.swatch" aria-label="{{ __('admin.attributes.swatch') }}">
                                                        @foreach ($swatches as $css => $labels)
                                                            <option value="{{ $css }}">{{ $labels[app()->getLocale()] }}@if (str_starts_with($css, 'linear')) · {{ __('admin.attributes.gradient') }}@endif</option>
                                                        @endforeach
                                                    </select>
                                                </span>
                                            </td>
                                        @endif
                                        <td class="is-shrink"><button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="removeValue('{{ $key }}')" aria-label="{{ __('admin.widgets.remove') }}"><x-icon name="trash-2" size="16" /></button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <x-slot:foot>
                    <x-button size="sm" variant="outline" icon="plus" wire:click="addValue">{{ __('admin.attributes.add_value') }}</x-button>
                </x-slot:foot>
            </x-admin.card>
        </div>

        <div style="display: grid; gap: 16px;">
            <x-admin.card :title="__('admin.attributes.usage')">
                <div style="display: grid; gap: 16px;">
                    <x-admin.switch wire:model="is_filterable" :label="__('admin.attributes.filterable')" :help="__('admin.attributes.filterable_help')" />
                    <x-admin.switch wire:model="is_variant_axis" :label="__('admin.attributes.used_for_variants')" :help="__('admin.attributes.variants_help')" />
                </div>
            </x-admin.card>
        </div>
    </div>
</div>

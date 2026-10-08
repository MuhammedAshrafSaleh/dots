@props([
    'label' => null,
    'items' => [],           // list of ['id' => ..., 'url' => ..., 'caption' => ...]
    'model' => null,         // wire:model for the (multiple) file input
    'reorder' => 'reorderGallery',   // Livewire method receiving the new id order
    'remove' => 'removeImage',       // Livewire method receiving an id
    'captionModel' => null,  // base path for per-item caption binding, e.g. "captions"
    'help' => null,
])
@php $id = 'gal-'.\Illuminate\Support\Str::random(6); @endphp
{{-- Mirror of admin-kit.jsx AGallery: drag to reorder, first is the cover,
     per-image caption, add button. --}}
<div class="d-field">
    @if ($label)
        <span class="d-label" style="display: flex; justify-content: space-between; gap: 8px;">
            {{ $label }}<span class="a-count">{{ $help ?? __('admin.widgets.gallery_hint') }}</span>
        </span>
    @endif

    <input type="file" id="{{ $id }}" accept="image/jpeg,image/png,image/webp" multiple wire:model="{{ $model }}"
           style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">

    <div x-data="sortable({ wire: $wire, method: '{{ $reorder }}' })"
         style="display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 10px;">
        @foreach ($items as $i => $item)
            <div class="a-card" data-sortable-key="{{ $item['id'] }}" draggable="true"
                 @dragstart="start($event, {{ $i }})" @dragover="over($event)" @drop="drop($event, {{ $i }})"
                 style="overflow: hidden; cursor: grab; position: relative;">
                <x-image-frame :src="$item['url']" ratio="4 / 3" />
                <span style="position: absolute; top: 6px; inset-inline-start: 6px;">
                    <x-badge :tone="$i === 0 ? 'accent' : 'outline'">{{ $i === 0 ? __('admin.widgets.cover') : $i + 1 }}</x-badge>
                </span>
                @if ($captionModel)
                    <div style="padding: 6px;">
                        <x-input size="sm" :placeholder="__('admin.widgets.caption')" wire:model.blur="{{ $captionModel }}.{{ $item['id'] }}" />
                    </div>
                @endif
                <div style="display: flex; align-items: center; padding: 2px 4px;">
                    <span class="a-grip"><x-icon name="grip-vertical" size="16" /></span>
                    <span style="flex: 1;"></span>
                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="{{ $remove }}('{{ $item['id'] }}')" aria-label="{{ __('admin.widgets.remove') }}">
                        <x-icon name="trash-2" size="16" />
                    </button>
                </div>
            </div>
        @endforeach
        <label for="{{ $id }}" class="a-drop" style="min-height: 120px;">
            <x-icon name="image-plus" size="22" />{{ __('admin.widgets.add_images') }}
        </label>
    </div>

    @error($model)<span class="d-help d-help--error"><x-icon name="circle-alert" size="14" />{{ $message }}</span>@enderror
    @error($model.'.*')<span class="d-help d-help--error"><x-icon name="circle-alert" size="14" />{{ $message }}</span>@enderror
</div>

@props([
    'label' => null,
    'url' => null,          // existing/preview image URL (null = empty)
    'model' => null,        // wire:model for the file <input> (parent uses WithFileUploads)
    'remove' => null,       // Livewire method name to clear the image
    'help' => null,
    'ratio' => '16 / 9',
    'contain' => false,
    'round' => false,
    'accept' => 'image/jpeg,image/png,image/webp',
])
@php $id = 'img-'.\Illuminate\Support\Str::random(6); @endphp
{{-- Mirror of admin-kit.jsx AImage: preview with Replace/Remove, drop zone when empty. --}}
<div class="d-field">
    @if ($label)<span class="d-label">{{ $label }}</span>@endif

    <input type="file" id="{{ $id }}" accept="{{ $accept }}" wire:model="{{ $model }}"
           style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">

    @if ($url)
        <div class="a-card" style="overflow: hidden;">
            <div style="{{ $contain ? 'background: var(--ink-50); padding: 16px;' : '' }}">
                <x-image-frame :src="$url" :ratio="$ratio" :contain="$contain"
                    :radius="$round ? '50%' : null"
                    :style="$round ? 'width: 96px; margin: 0 auto;' : null" />
            </div>
            <div style="display: flex; gap: 6px; align-items: center; padding: 6px 8px; border-top: 1px solid var(--border-1);">
                <span class="a-count" style="flex: 1; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $help ?? '' }}</span>
                <label for="{{ $id }}" class="d-btn d-btn--ghost d-btn--sm"><x-icon name="upload" size="16" />{{ __('admin.widgets.replace') }}</label>
                @if ($remove)
                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="{{ $remove }}" aria-label="{{ __('admin.widgets.remove') }}">
                        <x-icon name="trash-2" size="16" />
                    </button>
                @endif
            </div>
        </div>
    @else
        <label for="{{ $id }}" class="a-drop" style="aspect-ratio: {{ $ratio }}; width: 100%;" wire:loading.class="is-busy" wire:target="{{ $model }}">
            <x-icon name="image-plus" size="22" />
            <b style="color: var(--fg-1);">{{ __('admin.widgets.drop_image') }}</b>
            <span>JPG, PNG, WebP · 5 MB</span>
        </label>
    @endif

    @error($model)<span class="d-help d-help--error"><x-icon name="circle-alert" size="14" />{{ $message }}</span>@enderror
</div>

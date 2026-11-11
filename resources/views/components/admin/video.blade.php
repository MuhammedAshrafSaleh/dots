@props([
    'label' => null,
    'url' => null,          // existing video URL (null = empty)
    'model' => null,        // wire:model for the file <input>
    'remove' => null,       // Livewire method name to clear the video
    'help' => null,
])
@php $id = 'vid-'.\Illuminate\Support\Str::random(6); @endphp
<div class="d-field">
    @if ($label)<span class="d-label">{{ $label }}</span>@endif

    <input type="file" id="{{ $id }}" accept="video/mp4,video/webm"
           wire:model="{{ $model }}" tabindex="-1"
           style="position: fixed; top: 0; left: 0; width: 1px; height: 1px; opacity: 0; pointer-events: none;">

    @if ($url)
        <div class="a-card" style="overflow: hidden;">
            <video src="{{ $url }}" controls
                   style="width: 100%; max-height: 240px; display: block; background: #000;"></video>
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
        <label for="{{ $id }}" class="a-drop" style="aspect-ratio: 16 / 7; width: 100%;" wire:loading.class="is-busy" wire:target="{{ $model }}">
            <x-icon name="film" size="22" />
            <b style="color: var(--fg-1);">{{ __('admin.widgets.drop_video') }}</b>
            <span>{{ __('admin.widgets.video_hint') }}</span>
        </label>
    @endif

    @error($model)<span class="d-help d-help--error"><x-icon name="circle-alert" size="14" />{{ $message }}</span>@enderror
</div>

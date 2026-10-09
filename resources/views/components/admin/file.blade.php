@props([
    'label' => null,
    'name' => null,         // existing file name (null = empty)
    'model' => null,        // wire:model for the file <input> (parent uses WithFileUploads)
    'remove' => null,       // Livewire method name to clear the file
    'ext' => 'PDF',
    'meta' => null,         // e.g. file size
    'accept' => 'application/pdf',
])
@php $id = 'file-'.\Illuminate\Support\Str::random(6); @endphp
{{-- Mirror of admin-kit.jsx AFile. --}}
<div class="d-field">
    @if ($label)<span class="d-label">{{ $label }}</span>@endif

    <input type="file" id="{{ $id }}" accept="{{ $accept }}" wire:model="{{ $model }}"
           style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); border: 0;">

    <div class="d-file" @if (! $name) style="border-style: dashed; background: var(--ink-50);" @endif>
        <span class="d-file__ico"><x-icon name="file-text" size="20" /><span class="d-file__ext">{{ $ext }}</span></span>
        <span style="min-width: 0; flex: 1;">
            <span class="d-file__name" style="display: block;">{{ $name ?? __('admin.widgets.no_file') }}</span>
            <span class="d-file__meta">{{ $name ? ($meta ?? '') : __('admin.widgets.up_to_25mb') }}</span>
        </span>
        @if ($name)
            <span style="display: flex; gap: 4px;">
                <label for="{{ $id }}" class="d-btn d-btn--ghost d-btn--sm">{{ __('admin.widgets.replace') }}</label>
                @if ($remove)
                    <button type="button" class="d-btn d-btn--ghost d-btn--icon d-btn--sm" wire:click="{{ $remove }}" aria-label="{{ __('admin.widgets.remove') }}">
                        <x-icon name="trash-2" size="16" />
                    </button>
                @endif
            </span>
        @else
            <label for="{{ $id }}" class="d-btn d-btn--outline d-btn--sm"><x-icon name="upload" size="16" />{{ __('admin.widgets.upload') }}</label>
        @endif
    </div>

    @error($model)<span class="d-help d-help--error"><x-icon name="circle-alert" size="14" />{{ $message }}</span>@enderror
</div>

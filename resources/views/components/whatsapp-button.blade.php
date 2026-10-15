<a href="{{ $href }}"
   target="_blank"
   rel="noopener noreferrer"
   class="d-btn d-btn--whatsapp {{ $class }}"
   data-source="{{ $source }}"
   @if($iconOnly) aria-label="{{ $label ?: __('ui.whatsapp_cta') }}" @endif
>
    <x-icon name="whatsapp" />
    @unless($iconOnly)
        {{ $label ?: __('ui.whatsapp_cta') }}
    @endunless
</a>

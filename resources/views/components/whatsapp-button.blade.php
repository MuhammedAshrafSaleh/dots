<a href="{{ $href }}"
   target="_blank"
   rel="noopener noreferrer"
   class="d-btn d-btn--whatsapp {{ $class }}"
   data-source="{{ $source }}"
>
    <x-icon name="whatsapp" />
    {{ $label ?: __('ui.whatsapp_cta') }}
</a>

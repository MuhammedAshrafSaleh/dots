<a href="{{ $href }}"
   target="_blank"
   rel="noopener noreferrer"
   class="d-btn d-btn--wa {{ $class }}"
   data-source="{{ $source }}"
>
    <x-icon name="whatsapp" />
    {{ $label ?: __('ui.whatsapp_cta') }}
</a>

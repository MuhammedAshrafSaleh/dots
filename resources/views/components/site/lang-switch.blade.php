@php
    // EN / عربي segmented switch. Each button is a link to the same page in the
    // other locale (Locale::switchUrl preserves path + query). Mirror of LangSwitch.jsx.
    use App\Support\Locale;

    $current = app()->getLocale();
@endphp
<div class="d-lang" role="group" aria-label="{{ __('ui.language') }}">
    <a lang="en" href="{{ Locale::switchUrl('en') }}" hreflang="en"
        @if ($current === 'en') aria-current="true" @endif aria-pressed="{{ $current === 'en' ? 'true' : 'false' }}">EN</a>
    <a lang="ar" href="{{ Locale::switchUrl('ar') }}" hreflang="ar-EG"
        @if ($current === 'ar') aria-current="true" @endif aria-pressed="{{ $current === 'ar' ? 'true' : 'false' }}">عربي</a>
</div>

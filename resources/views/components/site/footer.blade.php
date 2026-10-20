@php
    // Site footer: brand + newsletter column, link columns (Shop from footer
    // categories, Company, Help from footer pages), legal/payment/social strip.
    // Copy from settings + the global.newsletter section. Mirror of Footer.jsx.
    use App\Models\PageSection;
    use App\Services\Navigation;
    use App\Services\Settings;

    $settings = app(Settings::class);
    $nav = app(Navigation::class);
    $footerCats = $nav->footerCategories();
    $footerPages = $nav->footerPages();
    $newsletter = PageSection::where('page', 'global')->where('key', 'newsletter')->first();

    $aboutText = $settings->translated('footer.about_text', $locale);
    $payments = $settings->translated('footer.payments_line', $locale);
    $copyright = $settings->translated('footer.copyright', $locale);

    $socials = array_filter([
        'instagram' => $settings->get('contact.social_instagram'),
        'facebook' => $settings->get('contact.social_facebook'),
        'linkedin' => $settings->get('contact.social_linkedin'),
        'youtube' => $settings->get('contact.social_youtube'),
    ]);
    $catUrl = fn ($cat) => route('category', ['category' => $cat->getTranslation('slug', $locale)]);
@endphp
<footer class="d-footer">
    <div class="d-container d-footer__top">
        <div style="display:grid;gap:18px;align-content:start;">
            <a href="{{ route('home') }}" class="d-nav__logo" style="font:700 30px/1 var(--font-display);text-decoration:none;color:inherit;">Dots</a>
            <p style="margin:0;font:500 14px/1.6 var(--font-body);color:var(--fg-2);max-width:340px;">{{ $aboutText }}</p>
            <form method="POST" action="{{ route('newsletter.subscribe') }}" style="display:flex;gap:8px;max-width:360px;">
                @csrf
                <input class="d-input" type="email" name="email" required
                    placeholder="{{ $newsletter?->field('title', $locale) ?: __('ui.newsletter_placeholder') }}"
                    aria-label="{{ __('ui.email') }}" />
                <x-button variant="dark" type="submit">{{ __('ui.join') }}</x-button>
            </form>
        </div>

        {{-- Shop (categories flagged for the footer) --}}
        <div>
            <h2>{{ __('ui.footer_shop') }}</h2>
            <ul>
                @foreach ($footerCats as $cat)
                    <li><a href="{{ $catUrl($cat) }}">{{ $cat->getTranslation('name', $locale) }}</a></li>
                @endforeach
                <li><a href="{{ route('brands') }}">{{ __('ui.all_brands') }}</a></li>
            </ul>
        </div>

        {{-- Services --}}
        <div>
            <h2>{{ __('ui.services') }}</h2>
            <ul>
                <li><a href="{{ route('services') }}">{{ __('ui.all_services') }}</a></li>
            </ul>
        </div>

        {{-- Company --}}
        <div>
            <h2>{{ __('ui.footer_company') }}</h2>
            <ul>
                <li><a href="{{ route('about') }}">{{ __('ui.about') }}</a></li>
                <li><a href="{{ route('projects.current') }}">{{ __('ui.current_projects') }}</a></li>
                <li><a href="{{ route('projects.previous') }}">{{ __('ui.previous_projects') }}</a></li>
                <li><a href="{{ route('blog') }}">{{ __('ui.blog') }}</a></li>
            </ul>
        </div>

        {{-- Help (content pages flagged for the footer + track order) --}}
        <div>
            <h2>{{ __('ui.footer_help') }}</h2>
            <ul>
                <li><a href="{{ route('track') }}">{{ __('ui.track_order') }}</a></li>
                @foreach ($footerPages as $page)
                    <li><a href="{{ route('page', ['page' => $page->getTranslation('slug', $locale)]) }}">{{ $page->getTranslation('title', $locale) }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="d-container d-footer__bottom">
        <span>{{ $copyright }}</span>
        <span>{{ $payments }}</span>
        @if ($socials !== [])
            <div class="d-footer__social">
                @foreach ($socials as $name => $url)
                    <a href="{{ $url }}" target="_blank" rel="noopener" aria-label="{{ ucfirst($name) }}" class="d-btn d-btn--outline d-btn--icon d-btn--sm">
                        <x-icon :name="$name" :size="18" />
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</footer>

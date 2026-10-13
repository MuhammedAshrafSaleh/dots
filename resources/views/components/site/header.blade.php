@php
    // Site header. Two variants toggled by the `.d-compact` root class (set by
    // viewport, see app.js): full (util strip + main bar + nav row + mega) and
    // compact (hamburger + logo + actions + search + slide-down menu).
    // Mirror of design/website/src/components/navigation/Navbar.jsx.
    use App\Services\Navigation;
    use App\Services\Settings;
    use Illuminate\Support\Facades\URL;

    $settings = app(Settings::class);
    $roots = app(Navigation::class)->megaTree();
    $hotline = $settings->get('contact.hotline');
    $showroom = $settings->translated('contact.showroom_address', $locale);
    $quoteLink = URL::signedRoute('wa.redirect', [
        'locale'   => $locale,
        'source'   => 'general',
        'ref_url'  => request()->url(),
    ]);

    // Active nav section for the current route (highlights the matching link).
    $active = $active ?? null;
    $isActive = fn ($key) => $active === $key ? 'd-nav__link--active' : '';
@endphp

{{-- ========================= Full (desktop) ========================= --}}
<header class="d-nav d-nav--full" x-data="{ mega: false, proj: false, acct: false }"
    x-on:mouseleave="mega = false; proj = false" x-on:keydown.escape="mega = false; proj = false; acct = false">
    <div class="d-nav__util">
        <div class="d-container d-nav__util-in">
            <span><x-icon name="phone" :size="14" />{{ __('ui.hotline') }} {{ $hotline }}</span>
            <span><x-icon name="map-pin" :size="14" />{{ $showroom }}</span>
            <span><x-icon name="truck" :size="14" />{{ __('ui.delivery_across_egypt') }}</span>
            <a href="{{ route('track') }}" style="margin-inline-start:auto;color:inherit;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><x-icon name="package-search" :size="14" />{{ __('ui.track_order') }}</a>
        </div>
    </div>
    <div class="d-container d-nav__main">
        <a href="{{ route('home') }}" class="d-nav__logo" aria-label="Dots — {{ __('ui.home') }}">
            <span style="font:700 26px/1 var(--font-display);">Dots</span>
        </a>
        <x-site.search-bar class="d-nav__search" />
        <div class="d-nav__actions">
            <x-site.lang-switch />
            {{-- Account icon: state only for now; auth lands in Phase 8. --}}
            <x-icon-button icon="user" :label="__('ui.login_register')" />
            {{-- Cart count placeholder; cart lands in Phase 7. --}}
            <x-icon-button icon="shopping-bag" :label="__('ui.cart')" :count="0" />
        </div>
    </div>
    <div class="d-nav__row">
        <div class="d-container d-nav__row-in">
            <button type="button" class="d-nav__link" :class="{ 'd-nav__link--open': mega }" :aria-expanded="mega"
                x-on:click="proj = false; mega = !mega">
                <x-icon x-show="!mega" name="layout-grid" :size="18" />
                <x-icon x-show="mega" x-cloak name="x" :size="18" />{{ __('ui.categories') }}
            </button>
            <span class="d-nav__sep"></span>
            <a href="{{ route('products') }}" class="d-nav__link {{ $isActive('products') }}">{{ __('ui.products') }}</a>
            <a href="{{ route('brands') }}" class="d-nav__link {{ $isActive('brands') }}">{{ __('ui.brands') }}</a>
            <a href="{{ route('services') }}" class="d-nav__link {{ $isActive('services') }}">{{ __('ui.services') }}</a>
            <div class="d-nav__dd" x-on:mouseenter="mega = false; proj = true" x-on:mouseleave="proj = false">
                <button type="button" class="d-nav__link {{ $isActive('projects') }}" :aria-expanded="proj" x-on:click="proj = !proj">
                    {{ __('ui.projects') }}<x-icon name="chevron-down" :size="16" ::style="proj ? 'transform:rotate(180deg)' : ''" />
                </button>
                <div class="d-nav__menu" role="menu" x-show="proj" x-cloak x-transition.opacity>
                    <a href="{{ route('projects.current') }}" role="menuitem" class="d-nav__mi">
                        <span class="d-nav__mi-ico"><x-icon name="hard-hat" :size="20" /></span>
                        <span><b>{{ __('ui.current_projects') }}</b><small>{{ __('ui.current_projects_sub') }}</small></span>
                    </a>
                    <a href="{{ route('projects.previous') }}" role="menuitem" class="d-nav__mi">
                        <span class="d-nav__mi-ico"><x-icon name="badge-check" :size="20" /></span>
                        <span><b>{{ __('ui.previous_projects') }}</b><small>{{ __('ui.previous_projects_sub') }}</small></span>
                    </a>
                </div>
            </div>
            <a href="{{ route('blog') }}" class="d-nav__link {{ $isActive('blog') }}">{{ __('ui.blog') }}</a>
            <a href="{{ route('about') }}" class="d-nav__link {{ $isActive('about') }}">{{ __('ui.about') }}</a>
            <a href="{{ $quoteLink }}" class="d-nav__quote" target="_blank" rel="noopener"><x-icon name="message-circle" :size="16" />{{ __('ui.request_quote') }}</a>
        </div>
    </div>
    <x-site.mega-menu />
</header>

{{-- ========================= Compact (mobile) ========================= --}}
<header class="d-nav d-nav--compact" x-data="{ open: false }">
    <div class="d-container d-nav__main">
        <button type="button" class="d-btn d-btn--ghost d-btn--icon" :aria-label="open ? @js(__('ui.close')) : @js(__('ui.menu'))" x-on:click="open = !open">
            <x-icon x-show="!open" name="menu" :size="20" />
            <x-icon x-show="open" x-cloak name="x" :size="20" />
        </button>
        <a href="{{ route('home') }}" class="d-nav__logo" aria-label="Dots — {{ __('ui.home') }}">
            <span style="font:700 22px/1 var(--font-display);">Dots</span>
        </a>
        <div class="d-nav__actions">
            <x-site.lang-switch />
            <x-icon-button icon="user" :label="__('ui.login_register')" />
            <x-icon-button icon="shopping-bag" :label="__('ui.cart')" :count="0" />
        </div>
    </div>
    <div class="d-container" style="padding-bottom:12px;">
        <x-site.search-bar :scopes="false" size="sm" iconOnly />
    </div>
    <nav x-show="open" x-cloak x-transition style="border-top:1px solid var(--border-1);padding:8px 16px 16px;display:grid;gap:2px;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;padding-bottom:12px;margin-bottom:6px;border-bottom:1px solid var(--border-1);">
            <x-button variant="primary">{{ __('ui.login') }}</x-button>
            <x-button variant="outline">{{ __('ui.create_account') }}</x-button>
        </div>
        <a href="{{ route('products') }}" class="d-mega__cat"><x-icon name="layout-grid" :size="20" />{{ __('ui.all_products') }}<x-icon name="chevron-right" :size="16" /></a>
        @foreach ($roots as $root)
            <a href="{{ route('category', ['category' => $root->getTranslation('slug', $locale)]) }}" class="d-mega__cat"><x-icon :name="$root->icon ?? 'layout-grid'" :size="20" />{{ $root->getTranslation('name', $locale) }}<x-icon name="chevron-right" :size="16" /></a>
        @endforeach
        <a href="{{ route('brands') }}" class="d-mega__cat"><x-icon name="badge-check" :size="20" />{{ __('ui.brands') }}<x-icon name="chevron-right" :size="16" /></a>
        <a href="{{ route('services') }}" class="d-mega__cat"><x-icon name="wrench" :size="20" />{{ __('ui.services') }}<x-icon name="chevron-right" :size="16" /></a>
        <a href="{{ route('projects.current') }}" class="d-mega__cat"><x-icon name="hard-hat" :size="20" />{{ __('ui.current_projects') }}<x-icon name="chevron-right" :size="16" /></a>
        <a href="{{ route('projects.previous') }}" class="d-mega__cat"><x-icon name="building-2" :size="20" />{{ __('ui.previous_projects') }}<x-icon name="chevron-right" :size="16" /></a>
        <a href="{{ route('blog') }}" class="d-mega__cat"><x-icon name="newspaper" :size="20" />{{ __('ui.blog') }}<x-icon name="chevron-right" :size="16" /></a>
        <a href="{{ route('about') }}" class="d-mega__cat"><x-icon name="info" :size="20" />{{ __('ui.about') }}<x-icon name="chevron-right" :size="16" /></a>
    </nav>
</header>

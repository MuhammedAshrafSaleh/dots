@props([
    'current',           // active tab id: overview|orders|wishlist|addresses|profile
    'counts' => [],      // ['orders'=>int,'wishlist'=>int,'addresses'=>int]
    'crumbs' => [],      // extra breadcrumb items after "My account"
    'heading' => null,   // optional <h1> shown above the slot (list pages)
])
@php
    /** @var \App\Models\Customer $user */
    $user = auth('customer')->user();
    $locale = app()->getLocale();

    $tabs = [
        ['id' => 'overview', 'label' => __('account.nav.overview'), 'icon' => 'layout-dashboard', 'route' => 'account.overview'],
        ['id' => 'orders', 'label' => __('account.nav.orders'), 'icon' => 'package', 'route' => 'account.orders'],
        ['id' => 'wishlist', 'label' => __('account.nav.wishlist'), 'icon' => 'heart', 'route' => 'account.wishlist'],
        ['id' => 'addresses', 'label' => __('account.nav.addresses'), 'icon' => 'map-pin', 'route' => 'account.addresses'],
        ['id' => 'profile', 'label' => __('account.nav.profile'), 'icon' => 'user', 'route' => 'account.profile'],
    ];

    $initial = \Illuminate\Support\Str::of($user->name)->trim()->substr(0, 1)->upper();

    $items = [
        ['label' => __('ui.home'), 'url' => route('home', $locale)],
        ['label' => __('account.nav.account'), 'url' => $current === 'overview' ? null : route('account.overview', $locale)],
    ];
    foreach ($crumbs as $c) {
        $items[] = $c;
    }
@endphp

<x-seo :noindex="true" />

<div style="background:var(--bg-subtle);">
    <div class="d-container" style="padding-block:28px 80px;display:grid;gap:24px;">
        <x-breadcrumbs :items="$items" />

        {{-- Mobile: horizontal tab bar (desktop side nav is hidden in compact). --}}
        <div class="d-account__tabs">
            <div role="tablist" class="d-tabs">
                @foreach ($tabs as $tab)
                    <a href="{{ route($tab['route'], $locale) }}" wire:navigate
                        role="tab" class="d-tab"
                        aria-selected="{{ $current === $tab['id'] ? 'true' : 'false' }}">
                        {{ $tab['label'] }}@isset($counts[$tab['id']])<span class="d-tab__n">{{ $counts[$tab['id']] }}</span>@endisset
                    </a>
                @endforeach
                <form method="POST" action="{{ route('logout', $locale) }}" style="display:contents;">
                    @csrf
                    <button type="submit" role="tab" class="d-tab" aria-selected="false">{{ __('account.nav.logout') }}</button>
                </form>
            </div>
        </div>

        <div class="d-account__grid">
            {{-- Desktop side nav --}}
            <aside class="d-account__side">
                <div class="d-account__usercard">
                    <span class="d-nav__acct-av">{{ $initial }}</span>
                    <span class="d-account__usercard-meta">
                        <b>{{ $user->name }}</b>
                        <small dir="ltr">{{ $user->email }}</small>
                    </span>
                </div>
                <nav class="d-account__nav" aria-label="{{ __('account.nav.account') }}">
                    @foreach ($tabs as $tab)
                        <a href="{{ route($tab['route'], $locale) }}" wire:navigate
                            class="d-mega__cat {{ $current === $tab['id'] ? 'd-mega__cat--on' : '' }}"
                            @if ($current === $tab['id']) aria-current="page" @endif>
                            <x-icon :name="$tab['icon']" size="20" /><span>{{ $tab['label'] }}</span>
                            @isset($counts[$tab['id']])<span class="d-account__nav-n">{{ $counts[$tab['id']] }}</span>@endisset
                        </a>
                    @endforeach
                    <span class="d-account__nav-sep"></span>
                    <form method="POST" action="{{ route('logout', $locale) }}" style="display:contents;">
                        @csrf
                        <button type="submit" class="d-mega__cat" style="width:100%;">
                            <x-icon name="log-out" size="20" /><span>{{ __('account.nav.logout') }}</span>
                        </button>
                    </form>
                </nav>
                <div class="d-account__help">
                    <b>{{ __('account.help.title') }}</b>
                    <span>{{ __('account.help.body') }}</span>
                    <div><x-whatsapp-button source="order_help" class="d-btn--sm" :label="__('account.help.cta')" /></div>
                </div>
            </aside>

            <div class="d-account__col">
                @if ($heading)
                    <h1 class="t-h2" style="margin:0;">{{ $heading }}</h1>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>
</div>

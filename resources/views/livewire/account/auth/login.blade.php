<div>
    <div class="d-auth__head">
        <x-eyebrow>{{ __('account.login.eyebrow') }}</x-eyebrow>
        <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ __('account.login.title') }}</h1>
        @if ($this->fromCheckout())
            <p class="d-auth__lede">{{ __('account.login.lede_checkout') }}</p>
        @endif
    </div>

    @if ($attemptsLeft !== null)
        <div class="d-auth__alert" role="alert">
            <span class="d-auth__alert-icon"><x-icon name="circle-alert" size="18" /></span>
            <div style="display:grid;gap:4px;padding-top:5px;">
                <b>{{ __('account.login.failed') }}</b>
                <span>{{ trans_choice('account.login.attempts_left', $attemptsLeft, ['count' => $attemptsLeft]) }}</span>
            </div>
        </div>
    @endif

    <form wire:submit="login" novalidate style="display:grid;gap:16px;">
        <x-input
            :label="__('account.login.identifier')"
            icon="user"
            wire:model="identifier"
            :error="$errors->first('identifier')"
            placeholder="01X XXXX XXXX · name@example.com"
            autocomplete="username"
            dir="ltr"
        />
        <x-site.password-input
            :label="__('account.login.password')"
            wire:model="password"
            :error="$errors->first('password')"
            autocomplete="current-password"
        />
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
            <x-checkbox :label="__('account.login.remember')" wire:model="remember" :checked="$remember" />
            <a href="{{ route('password.request', $locale) }}" wire:navigate style="font:800 14px/1.4 var(--font-body);color:var(--fg-1);text-decoration:underline;text-decoration-color:var(--accent);text-decoration-thickness:2px;text-underline-offset:3px;">{{ __('account.login.forgot') }}</a>
        </div>
        <x-button type="submit" size="lg" block iconEnd="arrow-right">{{ __('account.login.submit') }}</x-button>
    </form>

    <div style="display:grid;gap:14px;padding-top:18px;border-top:1px solid var(--border-1);">
        <span style="font:600 14px/1.5 var(--font-body);color:var(--fg-2);display:flex;gap:6px;flex-wrap:wrap;">
            {{ __('account.login.new_here') }}
            <a href="{{ route('register', $this->fromCheckout() ? ['locale' => $locale, 'next' => 'checkout'] : ['locale' => $locale]) }}" wire:navigate style="font-weight:800;color:var(--fg-1);text-decoration:underline;text-decoration-color:var(--accent);text-decoration-thickness:2px;text-underline-offset:3px;">{{ __('account.login.create') }}</a>
        </span>
        @if ($this->fromCheckout())
            <x-button :href="route('checkout', $locale)" variant="outline" size="lg" block>{{ __('account.login.guest') }}</x-button>
        @endif
    </div>
</div>

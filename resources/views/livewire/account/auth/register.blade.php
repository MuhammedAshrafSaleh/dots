<div>
    <div class="d-auth__head">
        <x-eyebrow>{{ __('account.register.eyebrow') }}</x-eyebrow>
        <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ __('account.register.title') }}</h1>
    </div>

    <form wire:submit="register" novalidate style="display:grid;gap:16px;">
        <x-input
            :label="__('account.register.name')"
            icon="user"
            wire:model="name"
            :error="$errors->first('name')"
            :placeholder="__('account.register.name_ph')"
            autocomplete="name"
        />
        <x-input
            :label="__('account.register.mobile')"
            icon="phone"
            wire:model="mobile"
            :error="$errors->first('mobile')"
            :help="__('account.register.mobile_help')"
            placeholder="01X XXXX XXXX"
            inputmode="tel"
            autocomplete="tel"
            dir="ltr"
        />
        <x-input
            :label="__('account.register.email')"
            icon="mail"
            type="email"
            wire:model="email"
            :error="$errors->first('email')"
            placeholder="name@example.com"
            autocomplete="email"
            dir="ltr"
        />
        <x-site.password-input
            :label="__('account.register.password')"
            wire:model="password"
            :error="$errors->first('password')"
            :help="__('account.register.password_help')"
            autocomplete="new-password"
        />
        <div style="display:grid;gap:6px;">
            <x-checkbox wire:model="terms" :checked="$terms">
                <span style="font-weight:500;color:var(--fg-2);">
                    {{ __('account.register.terms_pre') }}
                    <a href="{{ route('page', ['locale' => $locale, 'page' => __('account.register.terms_slug')]) }}" target="_blank" style="font-weight:800;color:var(--fg-1);">{{ __('account.register.terms') }}</a>
                    {{ __('account.register.terms_and') }}
                    <a href="{{ route('page', ['locale' => $locale, 'page' => __('account.register.privacy_slug')]) }}" target="_blank" style="font-weight:800;color:var(--fg-1);">{{ __('account.register.privacy') }}</a>
                </span>
            </x-checkbox>
            @error('terms')
                <span class="d-help d-help--error"><x-icon name="circle-alert" size="14" />{{ $message }}</span>
            @enderror
        </div>
        <x-button type="submit" size="lg" block iconEnd="arrow-right">{{ __('account.register.submit') }}</x-button>
    </form>

    <span class="d-auth__alt">
        {{ __('account.register.have_account') }}
        <a href="{{ route('login', $this->fromCheckout() ? ['locale' => $locale, 'next' => 'checkout'] : ['locale' => $locale]) }}" wire:navigate style="font-weight:800;color:var(--fg-1);text-decoration:underline;text-decoration-color:var(--accent);text-decoration-thickness:2px;text-underline-offset:3px;">{{ __('account.register.login') }}</a>
    </span>
</div>

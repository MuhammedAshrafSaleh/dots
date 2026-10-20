<div>
    @if ($invalid)
        <div class="d-auth__head">
            <span class="d-auth__icon d-auth__icon--error"><x-icon name="circle-alert" size="26" /></span>
            <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ __('account.reset.invalid_title') }}</h1>
            <p class="d-auth__lede">{{ __('account.reset.invalid_lede') }}</p>
        </div>
        <x-button :href="route('password.request', $locale)" size="lg" block iconEnd="arrow-right">{{ __('account.reset.request_new') }}</x-button>
    @elseif (! $done)
        <div class="d-auth__head">
            <span class="d-auth__icon"><x-icon name="lock" size="26" /></span>
            <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ __('account.reset.title') }}</h1>
            <p class="d-auth__lede">
                {{ __('account.reset.lede') }}
                <b dir="ltr" style="color:var(--fg-1);">{{ $email }}</b>
            </p>
        </div>

        @if ($errors->has('email'))
            <div class="d-auth__alert" role="alert">
                <span class="d-auth__alert-icon"><x-icon name="circle-alert" size="18" /></span>
                <div style="display:grid;gap:4px;padding-top:5px;">
                    <b>{{ $errors->first('email') }}</b>
                </div>
            </div>
        @endif

        <form wire:submit="save" novalidate style="display:grid;gap:16px;">
            <x-site.password-input
                :label="__('account.reset.new')"
                wire:model="password"
                :error="$errors->first('password')"
                :help="__('account.register.password_help')"
                autocomplete="new-password"
            />
            <x-site.password-input
                :label="__('account.reset.confirm')"
                wire:model="password_confirmation"
                autocomplete="new-password"
            />
            <x-button type="submit" size="lg" block>{{ __('account.reset.submit') }}</x-button>
        </form>
    @else
        <div class="d-auth__head">
            <span class="d-auth__icon d-auth__icon--success"><x-icon name="check" size="26" stroke="2" /></span>
            <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ __('account.reset.done_title') }}</h1>
            <p class="d-auth__lede">{{ __('account.reset.done_lede') }}</p>
        </div>
        <x-button :href="route('login', $locale)" size="lg" block iconEnd="arrow-right">{{ __('account.reset.login') }}</x-button>
    @endif
</div>

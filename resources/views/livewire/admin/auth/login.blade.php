<div>
    <form wire:submit="login" class="a-card" style="padding: 32px; display: grid; gap: 18px;" novalidate>
        <div style="display: grid; gap: 6px;">
            <h1 style="margin: 0; font: 600 24px/1.25 var(--font-display); letter-spacing: var(--tracking-heading);">{{ __('admin.login.title') }}</h1>
            <p style="margin: 0; font: 500 14px/1.5 var(--font-body); color: var(--fg-3);">{{ __('admin.login.subtitle') }}</p>
        </div>

        @error('email')
            <x-admin.alert tone="error" :title="__('admin.login.error_title')">
                @if (! is_null($attemptsLeft) && $attemptsLeft > 0)
                    {{ trans_choice('admin.login.attempts_left', $attemptsLeft, ['count' => $attemptsLeft]) }}
                @else
                    {{ $message }}
                @endif
            </x-admin.alert>
        @enderror

        <x-input
            type="email"
            icon="mail"
            dir="ltr"
            autocomplete="username"
            :label="__('admin.login.email')"
            wire:model="email"
            :error="$errors->has('email') && is_null($attemptsLeft) ? $errors->first('email') : null"
        />

        <div x-data="{ show: false }">
            <x-input
                x-bind:type="show ? 'text' : 'password'"
                type="password"
                icon="lock"
                dir="ltr"
                autocomplete="current-password"
                :label="__('admin.login.password')"
                wire:model="password"
                :error="$errors->first('password')"
            >
                <x-slot:trailing>
                    <button type="button" @click="show = !show"
                        :aria-label="show ? '{{ __('admin.login.hide_password') }}' : '{{ __('admin.login.show_password') }}'"
                        style="pointer-events: auto; border: 0; background: none; padding: 0; cursor: pointer; color: var(--fg-2); display: flex;">
                        <x-icon x-show="!show" name="eye" size="18" />
                        <x-icon x-show="show" name="eye-off" size="18" x-cloak />
                    </button>
                </x-slot:trailing>
            </x-input>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap;">
            <x-checkbox :label="__('admin.login.remember')" wire:model="remember" />
            <a class="a-link" href="{{ route('admin.password.request') }}">{{ __('admin.login.forgot') }}</a>
        </div>

        <x-button type="submit" size="lg" block>
            <span wire:loading.remove wire:target="login">{{ __('admin.login.submit') }}</span>
            <span wire:loading wire:target="login">{{ __('admin.login.submit') }}…</span>
        </x-button>
    </form>
</div>

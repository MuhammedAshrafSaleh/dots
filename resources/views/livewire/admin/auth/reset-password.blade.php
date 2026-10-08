<div>
    <form wire:submit="save" class="a-card" style="padding: 32px; display: grid; gap: 18px;" novalidate>
        <div style="display: grid; gap: 6px;">
            <h1 style="margin: 0; font: 600 24px/1.25 var(--font-display); letter-spacing: var(--tracking-heading);">{{ __('admin.reset.title') }}</h1>
            <p style="margin: 0; font: 500 14px/1.5 var(--font-body); color: var(--fg-3);">{{ __('admin.reset.subtitle') }}</p>
        </div>

        @error('email')
            <x-admin.alert tone="error">{{ $message }}</x-admin.alert>
        @enderror

        <x-input
            type="email"
            icon="mail"
            dir="ltr"
            autocomplete="username"
            :label="__('admin.reset.email')"
            wire:model="email"
        />

        <x-input
            type="password"
            icon="lock"
            dir="ltr"
            autocomplete="new-password"
            :label="__('admin.reset.password')"
            wire:model="password"
            :error="$errors->first('password')"
        />

        <x-input
            type="password"
            icon="lock"
            dir="ltr"
            autocomplete="new-password"
            :label="__('admin.reset.password_confirmation')"
            wire:model="password_confirmation"
        />

        <x-button type="submit" size="lg" block>
            <span wire:loading.remove wire:target="save">{{ __('admin.reset.submit') }}</span>
            <span wire:loading wire:target="save">{{ __('admin.reset.submit') }}…</span>
        </x-button>
    </form>
</div>

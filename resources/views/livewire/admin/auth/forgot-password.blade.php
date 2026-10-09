<div>
    <form wire:submit="send" class="a-card" style="padding: 32px; display: grid; gap: 18px;" novalidate>
        <div style="display: grid; gap: 6px;">
            <h1 style="margin: 0; font: 600 24px/1.25 var(--font-display); letter-spacing: var(--tracking-heading);">{{ __('admin.forgot.title') }}</h1>
            <p style="margin: 0; font: 500 14px/1.5 var(--font-body); color: var(--fg-3);">{{ __('admin.forgot.subtitle') }}</p>
        </div>

        @if ($sent)
            <x-admin.alert tone="success">{{ __('admin.forgot.sent') }}</x-admin.alert>
        @endif

        <x-input
            type="email"
            icon="mail"
            dir="ltr"
            autocomplete="username"
            :label="__('admin.forgot.email')"
            wire:model="email"
            :error="$errors->first('email')"
        />

        <x-button type="submit" size="lg" block>
            <span wire:loading.remove wire:target="send">{{ __('admin.forgot.submit') }}</span>
            <span wire:loading wire:target="send">{{ __('admin.forgot.submit') }}…</span>
        </x-button>

        <a class="a-link" href="{{ route('admin.login') }}" style="text-align: center;">{{ __('admin.forgot.back') }}</a>
    </form>
</div>

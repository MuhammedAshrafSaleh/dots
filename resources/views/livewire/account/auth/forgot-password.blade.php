<div x-data="{ cooldown: @entangle('cooldown') }"
     x-init="$watch('cooldown', v => { if (v > 0) { clearInterval($data._t); $data._t = setInterval(() => { if (cooldown > 0) cooldown--; else clearInterval($data._t); }, 1000); } })">
    @if (! $sent)
        <div class="d-auth__head">
            <span class="d-auth__icon"><x-icon name="key-round" size="26" /></span>
            <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ __('account.forgot.title') }}</h1>
            <p class="d-auth__lede">{{ __('account.forgot.lede') }}</p>
        </div>

        <form wire:submit="send" novalidate style="display:grid;gap:16px;">
            <x-input
                :label="__('account.forgot.email')"
                icon="mail"
                type="email"
                wire:model="email"
                :error="$errors->first('email')"
                placeholder="name@example.com"
                autocomplete="email"
                dir="ltr"
            />
            <x-button type="submit" size="lg" block>{{ __('account.forgot.send') }}</x-button>
        </form>

        <span class="d-auth__alt">
            <a href="{{ route('login', $locale) }}" wire:navigate style="font-weight:800;color:var(--fg-1);text-decoration:underline;text-decoration-color:var(--accent);text-decoration-thickness:2px;text-underline-offset:3px;">{{ __('account.forgot.back') }}</a>
        </span>
    @else
        <div class="d-auth__head">
            <span class="d-auth__icon"><x-icon name="mail-check" size="26" /></span>
            <h1 class="t-h2" style="margin:0;text-wrap:balance;">{{ __('account.forgot.sent_title') }}</h1>
            <p class="d-auth__lede">
                {{ __('account.forgot.sent_lede_pre') }}
                <b dir="ltr" style="color:var(--fg-1);">{{ $this->maskedEmail() }}</b>
                {{ __('account.forgot.sent_lede_post') }}
            </p>
        </div>

        <div style="display:grid;gap:10px;">
            <x-button :href="route('login', $locale)" size="lg" block>{{ __('account.forgot.back') }}</x-button>
            <x-button type="button" wire:click="resend" size="lg" variant="outline" block
                      x-bind:disabled="cooldown > 0">
                <span x-show="cooldown > 0" x-cloak x-text="'{{ __('account.forgot.resend_in') }}'.replace(':s', cooldown)"></span>
                <span x-show="cooldown <= 0">{{ __('account.forgot.resend') }}</span>
            </x-button>
        </div>

        <div style="display:grid;gap:10px;font:500 13px/1.55 var(--font-body);color:var(--fg-2);">
            <span>{{ __('account.forgot.spam') }}</span>
            <button type="button" wire:click="$set('sent', false)" style="border:0;background:none;padding:0;justify-self:start;font:800 13px/1.4 var(--font-body);color:var(--fg-1);text-decoration:underline;text-decoration-color:var(--accent);cursor:pointer;">{{ __('account.forgot.different') }}</button>
            <x-whatsapp-button source="general" :label="__('account.forgot.help')" />
        </div>
    @endif
</div>

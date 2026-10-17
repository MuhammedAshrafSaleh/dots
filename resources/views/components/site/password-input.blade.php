@props([
    'label' => null,
    'help' => null,
    'error' => null,
    'autocomplete' => 'current-password',
])
{{-- Mirror of Account.jsx KAPassword: masked input with a reveal toggle.
     Masks without JS (type="password"); Alpine flips it to text when shown. --}}
<div x-data="{ show: false }">
    <x-input
        :label="$label"
        :help="$help"
        :error="$error"
        icon="lock"
        type="password"
        x-bind:type="show ? 'text' : 'password'"
        dir="ltr"
        autocomplete="{{ $autocomplete }}"
        {{ $attributes }}
    >
        <x-slot:trailing>
            <button type="button" class="d-auth__pw-toggle"
                    x-on:click="show = ! show"
                    x-bind:aria-pressed="show"
                    x-bind:aria-label="show ? @js(__('account.password.hide')) : @js(__('account.password.show'))"
                    x-bind:title="show ? @js(__('account.password.hide')) : @js(__('account.password.show'))">
                <span x-show="! show"><x-icon name="eye" size="18" /></span>
                <span x-show="show" x-cloak><x-icon name="eye-off" size="18" /></span>
            </button>
        </x-slot:trailing>
    </x-input>
</div>

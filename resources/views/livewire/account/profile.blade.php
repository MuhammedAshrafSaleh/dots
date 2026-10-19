@php
    /** @var array{orders:int,wishlist:int,addresses:int} $counts */
    /** @var string $locale */
@endphp

<div>
    <x-site.account.shell current="profile" :counts="$counts" :heading="__('account.nav.profile')">
        <div class="d-account__cards2" style="align-items:start;">
            {{-- Personal details --}}
            <form wire:submit="saveDetails">
                <div class="d-account__box">
                    <h2 class="d-account__box-title">{{ __('account.profile.personal') }}</h2>
                    @if (session('account.flash_details'))
                        <div class="d-auth__alert d-auth__alert--success" role="status">
                            <span class="d-auth__alert-icon"><x-icon name="check" size="18" /></span>
                            <div style="padding-top:5px;"><b>{{ session('account.flash_details') }}</b></div>
                        </div>
                    @endif
                    <x-input :label="__('account.profile.name')" icon="user" wire:model="name" :error="$errors->first('name')" autocomplete="name" />
                    <x-input :label="__('account.profile.mobile')" icon="phone" wire:model="mobile" :error="$errors->first('mobile')" :help="__('account.profile.mobile_help')" inputmode="tel" dir="ltr" autocomplete="tel" />
                    <x-input :label="__('account.profile.email')" icon="mail" type="email" wire:model="email" :error="$errors->first('email')" :help="__('account.profile.email_help')" dir="ltr" autocomplete="email" />
                    <div><x-button type="submit">{{ __('account.profile.save') }}</x-button></div>
                </div>
            </form>

            {{-- Change password --}}
            <form wire:submit="updatePassword">
                <div class="d-account__box">
                    <h2 class="d-account__box-title">{{ __('account.profile.change_password') }}</h2>
                    @if (session('account.flash_password'))
                        <div class="d-auth__alert d-auth__alert--success" role="status">
                            <span class="d-auth__alert-icon"><x-icon name="check" size="18" /></span>
                            <div style="display:grid;gap:4px;padding-top:5px;"><b>{{ session('account.flash_password') }}</b><span>{{ __('account.profile.other_devices') }}</span></div>
                        </div>
                    @endif
                    <x-site.password-input :label="__('account.profile.current_password')" wire:model="current_password" :error="$errors->first('current_password')" autocomplete="current-password" />
                    <x-site.password-input :label="__('account.profile.new_password')" wire:model="password" :error="$errors->first('password')" :help="__('account.profile.password_help')" autocomplete="new-password" />
                    <x-site.password-input :label="__('account.profile.confirm_password')" wire:model="password_confirmation" :error="$errors->first('password_confirmation')" autocomplete="new-password" />
                    <div><x-button type="submit" variant="dark">{{ __('account.profile.update_password') }}</x-button></div>
                </div>
            </form>
        </div>
    </x-site.account.shell>
</div>

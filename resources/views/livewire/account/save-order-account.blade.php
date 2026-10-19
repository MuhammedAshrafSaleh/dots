<div>
    @if ($this->offerable())
        <form wire:submit="save" novalidate>
            <section style="border:1px solid var(--border-1);border-radius:var(--radius-lg);background:var(--white);padding:24px;display:grid;gap:16px;">
                <div style="display:grid;grid-template-columns:auto minmax(0,1fr);gap:14px;align-items:start;">
                    <span class="d-nav__mi-ico"><x-icon name="user" :size="20" /></span>
                    <div style="display:grid;gap:6px;">
                        <h2 style="margin:0;font:600 20px/1.25 var(--font-display);letter-spacing:var(--tracking-heading);">{{ __('account.save_order.title') }}</h2>
                        <span style="font:500 14px/1.55 var(--font-body);color:var(--fg-2);text-wrap:pretty;">{{ __('account.save_order.body') }}</span>
                    </div>
                </div>

                @php $chip = 'display:inline-flex;align-items:center;gap:6px;padding:6px 10px;border-radius:var(--radius-pill);background:var(--ink-50);font:600 13px var(--font-body);color:var(--fg-1);'; @endphp
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <span style="{{ $chip }}"><x-icon name="user" :size="14" />{{ $order->contact_name }}</span>
                    <span style="{{ $chip }}"><x-icon name="phone" :size="14" /><span dir="ltr">{{ $order->contact_mobile }}</span></span>
                    <span style="{{ $chip }}"><x-icon name="mail" :size="14" /><span dir="ltr">{{ $order->contact_email }}</span></span>
                </div>

                <div style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:start;" class="d-save-order__row">
                    <x-site.password-input
                        :label="__('account.save_order.password_label')"
                        wire:model="password"
                        :error="$errors->first('password')"
                        :help="__('account.register.password_help')"
                        autocomplete="new-password" />
                    <x-button type="submit" size="lg" style="margin-top:27px;" class="d-save-order__btn">{{ __('ui.create_account') }}</x-button>
                </div>
            </section>
        </form>
    @endif
</div>

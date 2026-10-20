@php
    use App\Enums\AddressLabel;
    use App\Models\CustomerAddress;

    /** @var \Illuminate\Support\Collection<int, \App\Models\CustomerAddress> $addresses */
    /** @var \Illuminate\Support\Collection<int, \App\Models\Governorate> $governorates */
    /** @var ?\App\Models\CustomerAddress $deleting */
    /** @var array{orders:int,wishlist:int,addresses:int} $counts */
    /** @var string $locale */

    $govOptions = $governorates->map(fn ($g) => ['value' => $g->id, 'label' => $g->name])->all();
    $atLimit = $addresses->count() >= CustomerAddress::MAX_PER_CUSTOMER;
@endphp

<div>
    <x-site.account.shell current="addresses" :counts="$counts">
        <div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;">
            <h1 class="t-h2" style="margin:0;">{{ __('account.nav.addresses') }}</h1>
            @if ($addresses->isNotEmpty() && ! $atLimit)
                <button type="button" wire:click="create" class="d-btn d-btn--dark"><x-icon name="plus" size="18" />{{ __('account.addresses.add') }}</button>
            @endif
        </div>

        @if (session('account.flash'))
            <div class="d-auth__alert d-auth__alert--success" role="status" wire:key="flash">
                <span class="d-auth__alert-icon"><x-icon name="check" size="18" /></span>
                <div style="padding-top:5px;"><b>{{ session('account.flash') }}</b></div>
            </div>
        @endif

        @if ($addresses->isNotEmpty())
            <div class="d-account__cards2">
                @foreach ($addresses as $a)
                    <article wire:key="addr-{{ $a->id }}" class="d-account__addr-card {{ $a->is_default ? 'd-account__addr-card--def' : '' }}">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <span class="d-nav__mi-ico" style="width:36px;height:36px;"><x-icon :name="$a->label->icon()" size="18" /></span>
                            <b style="font:800 16px var(--font-body);">{{ $a->label->label() }}</b>
                            @if ($a->is_default)<x-badge tone="accent">{{ __('account.addresses.default') }}</x-badge>@endif
                        </div>
                        <dl style="margin:0;display:grid;gap:8px;font:500 14px/1.5 var(--font-body);color:var(--fg-2);">
                            <div><b style="color:var(--fg-1);font-weight:800;">{{ $a->recipient_name }}</b> · <span dir="ltr">{{ $a->mobile }}</span></div>
                            @foreach ([
                                [__('account.addresses.governorate'), $a->governorate?->name],
                                [__('account.addresses.city'), $a->city],
                                [__('account.addresses.street'), $a->street],
                                [__('account.addresses.floor'), $a->floor_apartment],
                                [__('account.addresses.landmark'), $a->landmark],
                            ] as [$k, $v])
                                @if ($v)
                                    <div style="display:grid;grid-template-columns:128px minmax(0,1fr);gap:10px;">
                                        <dt style="font:600 12px/1.6 var(--font-body);color:var(--fg-3);">{{ $k }}</dt>
                                        <dd style="margin:0;color:var(--fg-1);">{{ $v }}</dd>
                                    </div>
                                @endif
                            @endforeach
                        </dl>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;padding-top:12px;border-top:1px solid var(--border-1);margin-top:auto;">
                            <button type="button" wire:click="edit({{ $a->id }})" class="d-btn d-btn--ghost d-btn--sm"><x-icon name="pencil" size="16" />{{ __('account.addresses.edit') }}</button>
                            <button type="button" wire:click="confirmDelete({{ $a->id }})" class="d-btn d-btn--ghost d-btn--sm"><x-icon name="trash-2" size="16" />{{ __('account.addresses.delete') }}</button>
                            @unless ($a->is_default)
                                <button type="button" wire:click="makeDefault({{ $a->id }})" class="d-btn d-btn--ghost d-btn--sm" style="margin-inline-start:auto;"><x-icon name="circle-check" size="16" />{{ __('account.addresses.set_default') }}</button>
                            @endunless
                        </div>
                    </article>
                @endforeach
                @if ($atLimit)
                    <div class="d-account__addr-add d-account__addr-add--limit" style="cursor:default;">
                        <span class="d-nav__mi-ico" style="background:var(--ink-50);"><x-icon name="map-pin-off" size="20" /></span>
                        <span style="font:600 13px/1.4 var(--font-body);color:var(--fg-2);">{{ __('account.addresses.limit_reached_short', ['max' => CustomerAddress::MAX_PER_CUSTOMER]) }}</span>
                    </div>
                @else
                    <button type="button" wire:click="create" class="d-account__addr-add">
                        <span class="d-nav__mi-ico"><x-icon name="plus" size="20" /></span>{{ __('account.addresses.add_new') }}
                    </button>
                @endif
            </div>
        @else
            <div class="d-account__empty">
                <span class="d-account__empty-ico"><x-icon name="map-pin" size="28" /></span>
                <h2>{{ __('account.addresses.empty_title') }}</h2>
                <p>{{ __('account.addresses.empty_body') }}</p>
                <div style="margin-top:6px;">
                    <button type="button" wire:click="create" class="d-btn d-btn--dark"><x-icon name="plus" size="18" />{{ __('account.addresses.add') }}</button>
                </div>
            </div>
        @endif

        {{-- Add / edit drawer --}}
        @if ($editingId !== null)
            <div class="d-overlay" wire:click="closeDrawer"></div>
            <aside class="d-drawer" role="dialog" aria-modal="true" aria-label="{{ $editingId === 0 ? __('account.addresses.add_new') : __('account.addresses.edit') }}">
                <div class="d-drawer__head">
                    <x-icon name="map-pin" size="20" />
                    <h3>{{ $editingId === 0 ? __('account.addresses.add_new') : __('account.addresses.edit') }}</h3>
                    <x-icon-button icon="x" :label="__('ui.close')" wire:click="closeDrawer" style="margin-inline-start:auto;" />
                </div>
                <form wire:submit="save" style="display:contents;">
                    <div class="d-drawer__body" style="padding-block:20px;display:grid;gap:16px;align-content:start;">
                        <div class="d-field">
                            <span class="d-label">{{ __('account.addresses.label') }}</span>
                            <div class="d-tabs d-tabs--pill" role="tablist">
                                @foreach (AddressLabel::cases() as $lbl)
                                    <button type="button" role="tab" class="d-tab" aria-selected="{{ $label === $lbl->value ? 'true' : 'false' }}" wire:click="$set('label', '{{ $lbl->value }}')">{{ $lbl->label() }}</button>
                                @endforeach
                            </div>
                        </div>
                        <x-input :label="__('account.addresses.recipient')" wire:model="recipient_name" :error="$errors->first('recipient_name')" />
                        <x-input :label="__('account.addresses.mobile')" wire:model="mobile" :error="$errors->first('mobile')" placeholder="01X XXXX XXXX" inputmode="tel" dir="ltr" />
                        <x-select :label="__('account.addresses.governorate')" :options="$govOptions" :placeholder="__('account.addresses.governorate_ph')" wire:model="governorate_id" :error="$errors->first('governorate_id')" />
                        <x-input :label="__('account.addresses.city')" wire:model="city" :error="$errors->first('city')" :placeholder="__('account.addresses.city_ph')" />
                        <x-input :label="__('account.addresses.street')" wire:model="street" :error="$errors->first('street')" :placeholder="__('account.addresses.street_ph')" />
                        <x-input :label="__('account.addresses.floor')" wire:model="floor_apartment" :error="$errors->first('floor_apartment')" placeholder="3 / 12" />
                        <x-input :label="__('account.addresses.landmark')" optional wire:model="landmark" :placeholder="__('account.addresses.landmark_ph')" />
                        <x-checkbox :label="__('account.addresses.use_default')" wire:model="is_default" :checked="$is_default" />
                    </div>
                    <div class="d-drawer__foot">
                        <x-button type="submit" size="lg" block>{{ $editingId === 0 ? __('account.addresses.save') : __('account.addresses.save_changes') }}</x-button>
                        <x-button type="button" variant="ghost" block wire:click="closeDrawer">{{ __('ui.cancel') }}</x-button>
                    </div>
                </form>
            </aside>
        @endif

        {{-- Delete confirm --}}
        @if ($deleting)
            <div class="d-overlay" wire:click="cancelDelete"></div>
            <div role="alertdialog" aria-modal="true" aria-labelledby="ka-del-t"
                style="position:fixed;z-index:var(--z-drawer);top:50%;left:50%;transform:translate(-50%,-50%);width:min(420px,calc(100% - 32px));box-sizing:border-box;background:var(--white);border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);padding:24px;display:grid;gap:14px;">
                <span style="width:48px;height:48px;border-radius:50%;background:var(--error-100);color:var(--error-600);display:grid;place-items:center;"><x-icon name="trash-2" size="22" /></span>
                <h3 id="ka-del-t" style="margin:0;font:600 20px/1.3 var(--font-display);">{{ __('account.addresses.delete_title') }}</h3>
                <p style="margin:0;font:500 14px/1.55 var(--font-body);color:var(--fg-2);"><b style="color:var(--fg-1);">{{ $deleting->label->label() }}</b> · {{ $deleting->street }}, {{ $deleting->city }}. {{ __('account.addresses.delete_note') }}</p>
                <div style="display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;">
                    <x-button variant="outline" wire:click="cancelDelete">{{ __('ui.cancel') }}</x-button>
                    <x-button variant="dark" icon="trash-2" wire:click="delete">{{ __('account.addresses.delete_confirm') }}</x-button>
                </div>
            </div>
        @endif
    </x-site.account.shell>
</div>

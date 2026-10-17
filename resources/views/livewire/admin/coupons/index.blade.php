@php
    $fmt = fn ($d) => $d?->translatedFormat('j M Y');
@endphp
<div class="a-page" x-data="{ drawer: $wire.entangle('showDrawer') }">
    <x-admin.head :title="__('admin.coupons.title')" :sub="__('admin.coupons.subtitle')">
        <x-slot:actions>
            @can('coupons.create')
                <x-button icon="plus" wire:click="openCreate">{{ __('admin.coupons.new') }}</x-button>
            @endcan
        </x-slot:actions>
    </x-admin.head>

    <section class="a-card">
        <div class="a-bar">
            <x-input class="a-grow" size="sm" icon="search" :placeholder="__('admin.coupons.col_code')"
                wire:model.live.debounce.300ms="search" :aria-label="__('admin.coupons.col_code')" />
        </div>

        <x-admin.table :empty="$rows->isEmpty()">
            <x-slot:head>
                <th>{{ __('admin.coupons.col_code') }}</th>
                <th>{{ __('admin.coupons.col_discount') }}</th>
                <th class="is-num">{{ __('admin.coupons.col_min') }}</th>
                <th>{{ __('admin.coupons.col_valid') }}</th>
                <th>{{ __('admin.coupons.col_used') }}</th>
                <th>{{ __('admin.coupons.col_status') }}</th>
                <th class="is-shrink">{{ __('admin.coupons.col_active') }}</th>
                <th class="is-shrink"></th>
            </x-slot:head>

            @foreach ($rows as $coupon)
                @php
                    $exhausted = $coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit;
                    $on = $coupon->is_active && ! $exhausted;
                @endphp
                <tr wire:key="coupon-{{ $coupon->id }}" class="is-click" wire:click="openEdit({{ $coupon->id }})">
                    <td><span class="a-mono" style="color: var(--fg-1); font-weight: 500;">{{ $coupon->code }}</span></td>
                    <td>
                        <b>{{ $coupon->type === \App\Enums\CouponType::Percent
                            ? (float) $coupon->value.'%'
                            : number_format((float) $coupon->value, 2).' '.__('admin.orders.egp') }}</b>
                    </td>
                    <td class="is-num">{{ number_format((float) $coupon->min_order_total, 2) }}</td>
                    <td>
                        <span class="a-mono" style="white-space: nowrap;">
                            @if ($coupon->starts_at || $coupon->ends_at)
                                {{ $fmt($coupon->starts_at) ?? '…' }} → {{ $fmt($coupon->ends_at) ?? '…' }}
                            @else
                                {{ __('admin.coupons.always') }}
                            @endif
                        </span>
                    </td>
                    <td style="white-space: nowrap; font-variant-numeric: tabular-nums; font-weight: 700;">
                        {{ number_format($coupon->used_count) }}{{ $coupon->usage_limit !== null ? ' / '.number_format($coupon->usage_limit) : ' · '.__('admin.coupons.no_limit') }}
                    </td>
                    <td><x-badge :tone="$on ? 'success' : 'neutral'" :dot="$on">{{ $on ? __('admin.coupons.status_on') : __('admin.coupons.status_off') }}</x-badge></td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        @can('coupons.update')
                            <x-admin.switch wire:click="toggleActive({{ $coupon->id }})" :checked="(bool) $coupon->is_active" />
                        @endcan
                    </td>
                    <td class="is-shrink" onclick="event.stopPropagation()">
                        @can('coupons.delete')
                            <x-admin.menu>
                                <button type="button" class="is-danger" wire:click="delete({{ $coupon->id }})"
                                        wire:confirm="{{ __('admin.coupons.delete_confirm') }}">
                                    <x-icon name="trash-2" size="16" />{{ __('admin.coupons.delete') }}
                                </button>
                            </x-admin.menu>
                        @endcan
                    </td>
                </tr>
            @endforeach

            <x-slot:emptyState>
                <x-admin.empty icon="ticket-percent" :title="__('admin.coupons.empty_title')" :body="__('admin.coupons.empty_body')" />
            </x-slot:emptyState>
        </x-admin.table>

        @if ($rows->hasPages())
            <div class="a-card__foot">{{ $rows->links() }}</div>
        @endif
    </section>

    <x-admin.drawer show="drawer" :title="$editingId ? $code : __('admin.coupons.drawer_new')">
        <x-slot:foot>
            <x-button variant="outline" x-on:click="drawer = false">{{ __('admin.coupons.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.coupons.save') }}</x-button>
        </x-slot:foot>

        <x-input :label="__('admin.coupons.code')" dir="ltr" style="text-transform: uppercase;"
            wire:model="code" :help="__('admin.coupons.code_help')" :error="$errors->first('code')">
            <x-slot:trailing>
                <button type="button" class="a-link" style="pointer-events: auto; font-size: 12px;" wire:click="generate">{{ __('admin.coupons.generate') }}</button>
            </x-slot:trailing>
        </x-input>

        <div class="d-field">
            <span class="d-label">{{ __('admin.coupons.type') }}</span>
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 10px;">
                <x-radio-card name="ctype" value="percent" :checked="$type === 'percent'" wire:model.live="type" icon="percent" :title="__('admin.coupons.type_percent')" />
                <x-radio-card name="ctype" value="fixed" :checked="$type === 'fixed'" wire:model.live="type" icon="banknote" :title="__('admin.coupons.type_fixed')" />
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
            <x-input :label="__('admin.coupons.value')" type="number" step="0.01" dir="ltr" wire:model="value" :error="$errors->first('value')">
                <x-slot:trailing><span style="font: 700 13px var(--font-body);">{{ $type === 'percent' ? '%' : 'EGP' }}</span></x-slot:trailing>
            </x-input>
            <x-input :label="__('admin.coupons.min_order')" type="number" step="0.01" dir="ltr" wire:model="min_order_total" :error="$errors->first('min_order_total')">
                <x-slot:trailing><span style="font: 700 13px var(--font-body);">EGP</span></x-slot:trailing>
            </x-input>
            <x-input :label="__('admin.coupons.starts')" type="date" dir="ltr" wire:model="starts_at" :error="$errors->first('starts_at')" optional />
            <x-input :label="__('admin.coupons.ends')" type="date" dir="ltr" wire:model="ends_at" :error="$errors->first('ends_at')" optional />
            <x-input :label="__('admin.coupons.usage_limit')" type="number" dir="ltr" wire:model="usage_limit" :placeholder="__('admin.coupons.no_limit')" :error="$errors->first('usage_limit')" optional />
            <x-input :label="__('admin.coupons.times_used')" :value="number_format($used_count)" disabled readonly dir="ltr" />
        </div>

        <x-admin.switch wire:model="once_per_customer" :label="__('admin.coupons.once_per_customer')" :help="__('admin.coupons.once_per_customer_help')" />
        <x-admin.switch wire:model="is_active" :label="__('admin.coupons.active')" :help="__('admin.coupons.active_help')" />
    </x-admin.drawer>
</div>

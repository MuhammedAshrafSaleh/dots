@php
    $locale = app()->getLocale();
    $tx = fn ($m, $f) => $m->getTranslation($f, $locale, false);
    $iconFor = ['cod' => 'banknote', 'card' => 'credit-card', 'instapay_whatsapp' => 'smartphone'];
@endphp
<div class="a-page" x-data="{ drawer: $wire.entangle('showDrawer') }">
    <x-admin.head :title="__('admin.payments.title')" :sub="__('admin.payments.subtitle')" />

    <div style="display: grid; gap: 12px;" x-data="sortable({ wire: $wire, method: 'reorder' })">
        @foreach ($rows as $i => $method)
            @php $isCard = $method->code->value === 'card'; @endphp
            <section wire:key="pm-{{ $method->id }}" class="a-card"
                     data-sortable-key="{{ $method->id }}" draggable="true"
                     @dragstart="start($event, {{ $i }})" @dragover="over($event)" @drop="drop($event, {{ $i }})"
                     style="padding: 18px; display: grid; gap: 14px;">
                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <span class="a-grip" style="cursor: grab;"><x-icon name="grip-vertical" size="16" /></span>
                    <span class="a-count" style="width: 14px;">{{ $i + 1 }}</span>
                    <span class="a-kpi__ico" style="width: 44px; height: 44px;"><x-icon :name="$method->icon ?: ($iconFor[$method->code->value] ?? 'wallet')" size="20" /></span>
                    <span style="flex: 1 1 220px; min-width: 0;">
                        <b style="font: 800 15px/1.3 var(--font-body); display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                            {{ $tx($method, 'name') }}
                            @if ($isCard)
                                <x-badge :tone="$paymobConfigured ? 'success' : 'warning'" dot>{{ $paymobConfigured ? __('admin.payments.configured') : __('admin.payments.not_configured') }}</x-badge>
                            @endif
                        </b>
                        <span class="a-sub">{{ $tx($method, 'description') }}</span>
                    </span>
                    @can('payment_methods.update')
                        <x-button variant="outline" size="sm" icon="pencil" wire:click="openEdit({{ $method->id }})">{{ __('admin.payments.edit') }}</x-button>
                        <x-admin.switch wire:click="toggleActive({{ $method->id }})" :checked="(bool) $method->is_active" :label="$method->is_active ? __('admin.payments.active') : __('admin.payments.off')" />
                    @endcan
                </div>
                @if ($isCard && ! $paymobConfigured)
                    <x-admin.alert tone="warning" :title="__('admin.payments.paymob_unconfigured_title')">
                        {{ __('admin.payments.paymob_unconfigured_body') }}
                    </x-admin.alert>
                @endif
            </section>
        @endforeach
    </div>

    <x-admin.drawer show="drawer" :width="600" :title="$editingId ? $name[$locale] : ''">
        <x-slot:foot>
            <x-button variant="outline" x-on:click="drawer = false">{{ __('admin.payments.cancel') }}</x-button>
            <x-button icon="check" wire:click="save">{{ __('admin.payments.save') }}</x-button>
        </x-slot:foot>

        <x-admin.field-bilingual :label="__('admin.payments.name')" model="name" />
        @error('name.en') <span class="d-help d-help--error">{{ $message }}</span> @enderror
        @error('name.ar') <span class="d-help d-help--error">{{ $message }}</span> @enderror
        <x-admin.field-bilingual :label="__('admin.payments.description')" model="description" />
        <x-admin.field-bilingual :label="__('admin.payments.instructions')" model="instructions" multiline :rows="3" />

        <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px; align-items: end;">
            <x-input :label="__('admin.payments.order')" type="number" dir="ltr" wire:model="sort_order" :error="$errors->first('sort_order')" />
            <div style="padding-bottom: 8px;">
                <x-admin.switch wire:model="is_active" :label="__('admin.payments.active')" />
            </div>
        </div>

        @if ($editingCode === 'card')
            <x-admin.card :title="__('admin.payments.paymob_status')" icon="shield-check">
                @if ($paymobConfigured)
                    <x-admin.alert tone="success" :title="__('admin.payments.paymob_configured_title')">{{ __('admin.payments.paymob_configured_body') }}</x-admin.alert>
                @else
                    <x-admin.alert tone="warning" :title="__('admin.payments.paymob_unconfigured_title')">{{ __('admin.payments.paymob_unconfigured_body') }}</x-admin.alert>
                @endif
                <x-input :label="__('admin.payments.webhook_url')" :value="$webhookUrl" readonly dir="ltr" :help="__('admin.payments.webhook_help')" />
                <x-input :label="__('admin.payments.card_window')" type="number" dir="ltr" wire:model="card_payment_window_minutes" :error="$errors->first('card_payment_window_minutes')" :help="__('admin.payments.card_window_help')">
                    <x-slot:trailing><span style="font: 700 13px var(--font-body);">{{ __('admin.payments.minutes') }}</span></x-slot:trailing>
                </x-input>
            </x-admin.card>
        @endif

        @if ($editingCode === 'instapay_whatsapp')
            <div style="display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 16px;">
                <x-input :label="__('admin.payments.instapay_address')" dir="ltr" wire:model="instapay_address" :error="$errors->first('instapay_address')" />
                <x-input :label="__('admin.payments.hold_hours')" type="number" dir="ltr" wire:model="instapay_hold_hours" :error="$errors->first('instapay_hold_hours')">
                    <x-slot:trailing><span style="font: 700 13px var(--font-body);">{{ __('admin.payments.hours') }}</span></x-slot:trailing>
                </x-input>
            </div>
        @endif
    </x-admin.drawer>
</div>

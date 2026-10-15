@php
    /**
     * @var \Illuminate\Support\Collection<int, \App\Models\CartItem> $items
     * @var \App\Support\CartTotals $totals
     * @var \Illuminate\Support\Collection<int, \App\Models\DeliveryMethod> $deliveryMethods
     * @var \Illuminate\Support\Collection<int, \App\Models\PaymentMethod> $paymentMethods
     * @var \Illuminate\Support\Collection<int, \App\Models\Governorate> $governorates
     * @var \Illuminate\Support\Collection<int, \App\Models\CustomerAddress> $savedAddresses
     * @var string $locale
     */
@endphp

<div style="background:var(--bg-subtle);">
    <div class="d-container" style="padding-block:28px 64px;">

        @if ($items->isEmpty())
            <div style="text-align:center;padding:64px 0;">
                <p style="color:var(--fg-3);margin-bottom:16px;">{{ __('ui.cart_empty') }}</p>
                <a href="{{ route('products', $locale) }}" class="d-btn d-btn--primary">{{ __('ui.continue_shopping') }}</a>
            </div>
        @else
            <nav aria-label="breadcrumb" style="font:600 13px/1.4 var(--font-body);color:var(--fg-3);margin-bottom:14px;">
                <a href="{{ route('cart', $locale) }}" style="color:var(--fg-2);">{{ __('ui.cart') }}</a>
                <span style="margin-inline:6px;">/</span>
                <span style="color:var(--fg-1);">{{ __('ui.checkout') }}</span>
            </nav>
            <h1 class="t-h2" style="margin:0 0 24px;">{{ __('ui.checkout') }}</h1>

            <div class="d-cart-grid" style="display:grid;grid-template-columns:minmax(0,1fr) 400px;gap:32px;align-items:start;">

                {{-- Left: the form --}}
                <form class="d-co" wire:submit="place">

                    {{-- 1. Contact --}}
                    <section class="d-co__sec">
                        <div class="d-co__head"><span class="d-co__num">1</span><h3>{{ __('ui.checkout_contact') }}</h3></div>
                        <div class="d-co__grid">
                            <div class="d-field">
                                <label class="d-label" for="co-name">{{ __('ui.full_name') }}</label>
                                <input id="co-name" type="text" class="d-input" wire:model="name" placeholder="{{ __('ui.full_name_ph') }}" @error('name') aria-invalid="true" @enderror>
                                @error('name') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                            </div>
                            <div class="d-field">
                                <label class="d-label" for="co-mobile">{{ __('ui.mobile_label') }}</label>
                                <input id="co-mobile" type="tel" dir="ltr" inputmode="tel" class="d-input" wire:model="mobile" placeholder="01X XXXX XXXX" @error('mobile') aria-invalid="true" @enderror>
                                @error('mobile') <span class="d-help d-help--error">{{ $message }}</span> @else <span class="d-help">{{ __('ui.checkout_mobile_help') }}</span> @enderror
                            </div>
                            <div class="d-field span-2">
                                <label class="d-label" for="co-email">{{ __('ui.email_label') }} <span class="d-label__opt">{{ __('ui.optional') }}</span></label>
                                <input id="co-email" type="email" dir="ltr" class="d-input" wire:model="email" placeholder="name@example.com" @error('email') aria-invalid="true" @enderror>
                                @error('email') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                            </div>
                            <label class="d-check span-2">
                                <input type="checkbox" wire:model.live="isCompany">
                                <span>{{ __('ui.checkout_is_company') }}</span>
                            </label>
                            @if ($isCompany)
                                <div class="d-field span-2">
                                    <label class="d-label" for="co-tax">{{ __('ui.checkout_tax_id') }}</label>
                                    <input id="co-tax" type="text" dir="ltr" class="d-input" wire:model="companyTaxId" placeholder="XXX-XXX-XXX" @error('companyTaxId') aria-invalid="true" @enderror>
                                    @error('companyTaxId') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                                </div>
                            @endif
                        </div>
                    </section>

                    {{-- 2. Delivery address --}}
                    <section class="d-co__sec">
                        <div class="d-co__head"><span class="d-co__num">2</span><h3>{{ __('ui.checkout_address') }}</h3></div>

                        @if ($savedAddresses->isNotEmpty())
                            <div style="display:grid;gap:10px;">
                                @foreach ($savedAddresses as $address)
                                    <label class="d-radio-card @if ((string) $address->id === $addressChoice) d-radio-card--on @endif">
                                        <input type="radio" wire:model.live="addressChoice" value="{{ $address->id }}" style="position:absolute;opacity:0;pointer-events:none;">
                                        <span class="d-check"><input type="radio" tabindex="-1" aria-hidden="true" @checked((string) $address->id === $addressChoice)></span>
                                        <span style="flex:1;min-width:0;display:grid;gap:3px;">
                                            <span style="font:800 14px/1.3 var(--font-body);display:flex;align-items:center;gap:8px;">
                                                <x-icon name="map-pin" :size="18" />{{ $address->recipient_name }}
                                                @if ($address->is_default)<span class="d-badge d-badge--neutral" style="margin-inline-start:4px;">{{ __('ui.default') }}</span>@endif
                                            </span>
                                            <span style="font:500 13px/1.45 var(--font-body);color:var(--fg-2);">
                                                {{ $address->street }}، {{ $address->city }}، {{ $address->governorate?->getTranslation('name', $locale) }}
                                            </span>
                                        </span>
                                    </label>
                                @endforeach
                                <label class="d-radio-card @if ($addressChoice === 'new') d-radio-card--on @endif">
                                    <input type="radio" wire:model.live="addressChoice" value="new" style="position:absolute;opacity:0;pointer-events:none;">
                                    <span class="d-check"><input type="radio" tabindex="-1" aria-hidden="true" @checked($addressChoice === 'new')></span>
                                    <span style="flex:1;min-width:0;display:grid;gap:3px;">
                                        <span style="font:800 14px/1.3 var(--font-body);display:flex;align-items:center;gap:8px;"><x-icon name="plus" :size="18" />{{ __('ui.checkout_new_address') }}</span>
                                        <span style="font:500 13px/1.45 var(--font-body);color:var(--fg-2);">{{ __('ui.checkout_new_address_hint') }}</span>
                                    </span>
                                </label>
                            </div>
                        @endif

                        @if ($addressChoice === 'new')
                            <div class="d-co__grid">
                                <div class="d-field">
                                    <label class="d-label" for="co-gov">{{ __('ui.governorate') }}</label>
                                    <select id="co-gov" class="d-input" wire:model.live="governorateId" @error('governorateId') aria-invalid="true" @enderror>
                                        <option value="">{{ __('ui.select_placeholder') }}</option>
                                        @foreach ($governorates as $gov)
                                            <option value="{{ $gov->id }}">{{ $gov->getTranslation('name', $locale) }}</option>
                                        @endforeach
                                    </select>
                                    @error('governorateId') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                                </div>
                                <div class="d-field">
                                    <label class="d-label" for="co-city">{{ __('ui.checkout_city') }}</label>
                                    <input id="co-city" type="text" class="d-input" wire:model="city" placeholder="{{ __('ui.checkout_city_ph') }}" @error('city') aria-invalid="true" @enderror>
                                    @error('city') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                                </div>
                                <div class="d-field span-2">
                                    <label class="d-label" for="co-street">{{ __('ui.checkout_street') }}</label>
                                    <input id="co-street" type="text" class="d-input" wire:model="street" placeholder="{{ __('ui.checkout_street_ph') }}" @error('street') aria-invalid="true" @enderror>
                                    @error('street') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                                </div>
                                <div class="d-field">
                                    <label class="d-label" for="co-floor">{{ __('ui.checkout_floor') }} <span class="d-label__opt">{{ __('ui.optional') }}</span></label>
                                    <input id="co-floor" type="text" class="d-input" wire:model="floorApartment" placeholder="3 / 12">
                                </div>
                                <div class="d-field">
                                    <label class="d-label" for="co-landmark">{{ __('ui.checkout_landmark') }} <span class="d-label__opt">{{ __('ui.optional') }}</span></label>
                                    <input id="co-landmark" type="text" class="d-input" wire:model="landmark" placeholder="{{ __('ui.checkout_landmark_ph') }}">
                                </div>
                                @auth('customer')
                                    <label class="d-check span-2">
                                        <input type="checkbox" wire:model="saveAddress">
                                        <span>{{ __('ui.checkout_save_address') }}</span>
                                    </label>
                                @endauth
                            </div>
                        @endif
                    </section>

                    {{-- 3. Delivery method --}}
                    <section class="d-co__sec">
                        <div class="d-co__head"><span class="d-co__num">3</span><h3>{{ __('ui.checkout_delivery') }}</h3></div>
                        <div style="display:grid;gap:10px;">
                            @forelse ($deliveryMethods as $method)
                                <label class="d-radio-card @if ($method->id === $deliveryMethodId) d-radio-card--on @endif">
                                    <input type="radio" wire:model.live="deliveryMethodId" value="{{ $method->id }}" style="position:absolute;opacity:0;pointer-events:none;">
                                    <span class="d-check"><input type="radio" tabindex="-1" aria-hidden="true" @checked($method->id === $deliveryMethodId)></span>
                                    <span style="flex:1;min-width:0;display:grid;gap:3px;">
                                        <span style="font:800 14px/1.3 var(--font-body);display:flex;align-items:center;gap:8px;">
                                            @if ($method->icon)<x-icon :name="$method->icon" :size="18" />@endif{{ $method->getTranslation('name', $locale) }}
                                        </span>
                                        @if ($method->getTranslation('description', $locale))
                                            <span style="font:500 13px/1.45 var(--font-body);color:var(--fg-2);">{{ $method->getTranslation('description', $locale) }}</span>
                                        @endif
                                    </span>
                                    <span style="font:800 14px/1.3 var(--font-body);white-space:nowrap;">
                                        @if ((float) $method->price == 0.0)
                                            {{ __('ui.delivery_free') }}
                                        @else
                                            @if ($method->price_is_from){{ __('ui.from') }} @endif{{ number_format((float) $method->price, 0) }} {{ __('ui.egp') }}
                                        @endif
                                    </span>
                                </label>
                            @empty
                                <p class="d-help">{{ __('ui.checkout_no_delivery') }}</p>
                            @endforelse
                        </div>
                        @error('deliveryMethodId') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                    </section>

                    {{-- 4. Payment --}}
                    <section class="d-co__sec">
                        <div class="d-co__head"><span class="d-co__num">4</span><h3>{{ __('ui.checkout_payment') }}</h3></div>
                        <div style="display:grid;gap:10px;">
                            @foreach ($paymentMethods as $method)
                                <label class="d-radio-card @if ($method->code->value === $paymentCode) d-radio-card--on @endif">
                                    <input type="radio" wire:model.live="paymentCode" value="{{ $method->code->value }}" style="position:absolute;opacity:0;pointer-events:none;">
                                    <span class="d-check"><input type="radio" tabindex="-1" aria-hidden="true" @checked($method->code->value === $paymentCode)></span>
                                    <span style="flex:1;min-width:0;display:grid;gap:3px;">
                                        <span style="font:800 14px/1.3 var(--font-body);display:flex;align-items:center;gap:8px;">
                                            @if ($method->icon)<x-icon :name="$method->icon" :size="18" />@endif{{ $method->getTranslation('name', $locale) }}
                                        </span>
                                        @if ($method->getTranslation('description', $locale))
                                            <span style="font:500 13px/1.45 var(--font-body);color:var(--fg-2);">{{ $method->getTranslation('description', $locale) }}</span>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('paymentCode') <span class="d-help d-help--error">{{ $message }}</span> @enderror
                    </section>
                </form>

                {{-- Right: order summary --}}
                <aside style="position:sticky;top:24px;">
                    <div class="d-summary">
                        <h3>{{ __('ui.order_summary') }}</h3>
                        <div>
                            @foreach ($items as $item)
                                @php
                                    $variant = $item->productVariant;
                                    $product = $variant->product;
                                    $vlabel = $variant->attributeValues->map(fn ($av) => $av->getTranslation('label', $locale))->implode(' · ');
                                @endphp
                                <div class="d-line">
                                    <div class="d-line__img">
                                        <x-image-frame :src="$product->getFirstMediaUrl('gallery')" :alt="$product->getTranslation('name', $locale)" />
                                        <span class="d-summary__qty">{{ $item->qty }}</span>
                                    </div>
                                    <div style="min-width:0;">
                                        <p class="d-line__name" style="font-size:13px;">{{ $product->getTranslation('name', $locale) }}</p>
                                        @if ($vlabel)<div class="d-line__var">{{ $vlabel }}</div>@endif
                                    </div>
                                    <div class="d-line__price" style="font-size:14px;">{{ number_format(round((float) $variant->price * $item->qty, 2), 0) }} {{ __('ui.egp') }}</div>
                                </div>
                            @endforeach
                        </div>

                        <div class="d-totals">
                            <div><span>{{ __('ui.subtotal') }}</span><b>{{ number_format($totals->subtotal, 0) }} {{ __('ui.egp') }}</b></div>
                            <div>
                                <span>{{ __('ui.delivery') }}</span>
                                <b>@if ($totals->deliveryFree || $totals->deliveryFee == 0.0){{ __('ui.delivery_free') }}@else{{ number_format($totals->deliveryFee, 0) }} {{ __('ui.egp') }}@endif</b>
                            </div>
                            @if ($totals->discount > 0)
                                <div style="color:var(--success-600);"><span>{{ __('ui.discount') }}</span><b style="color:var(--success-600);">− {{ number_format($totals->discount, 0) }} {{ __('ui.egp') }}</b></div>
                            @endif
                            <div class="d-totals__grand"><span>{{ __('ui.order_total') }}</span><b>{{ number_format($totals->total, 0) }} {{ __('ui.egp') }}</b></div>
                            <span style="font:500 12px var(--font-body);color:var(--fg-3);">{{ __('ui.cart_vat_note') }}</span>
                        </div>

                        <button type="button" class="d-btn d-btn--primary d-btn--lg d-btn--block" wire:click="place" wire:loading.attr="disabled">
                            {{ __('ui.place_order') }}<x-icon name="arrow-right" :size="18" />
                        </button>

                        <div class="d-trust">
                            <span><x-icon name="shield-check" :size="16" />{{ __('ui.trust_warranty') }}</span>
                            <span><x-icon name="rotate-ccw" :size="16" />{{ __('ui.trust_returns') }}</span>
                            <span><x-icon name="lock" :size="16" />{{ __('ui.trust_secure') }}</span>
                        </div>
                    </div>
                </aside>
            </div>
        @endif
    </div>
</div>

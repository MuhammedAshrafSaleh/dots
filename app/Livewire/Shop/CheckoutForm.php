<?php

namespace App\Livewire\Shop;

use App\Actions\PlaceOrder;
use App\Enums\PaymentMethodCode;
use App\Exceptions\CheckoutException;
use App\Models\CustomerAddress;
use App\Models\DeliveryMethod;
use App\Models\Governorate;
use App\Models\PaymentMethod;
use App\Services\CartManager;
use App\Services\Payments\PaymentException;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\PaymobGateway;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Single-page checkout: contact, company tax ID, governorate-filtered delivery
 * methods, saved/new address, payment method (card hidden when Paymob is
 * unconfigured) and placing the order in one DB transaction.
 */
class CheckoutForm extends Component
{
    public string $name = '';

    public string $mobile = '';

    public string $email = '';

    public bool $isCompany = false;

    public string $companyTaxId = '';

    /** Saved-address id as a string, or 'new'. Guests always use 'new'. */
    public string $addressChoice = 'new';

    public ?int $governorateId = null;

    public string $city = '';

    public string $street = '';

    public string $floorApartment = '';

    public string $landmark = '';

    public bool $saveAddress = true;

    public ?int $deliveryMethodId = null;

    public string $paymentCode = 'cod';

    /** Submit-time error that belongs near the order summary, not on any field. */
    public ?string $cartError = null;

    /** When true the cart-error banner also shows a "Return to cart" link. */
    public bool $cartErrorShowLink = false;

    /**
     * Lines that are unavailable or have insufficient stock, detected on mount.
     * Each entry carries the current stock (`available`) and the quantity the
     * customer has in the cart (`requested`) so the banner can show both.
     *
     * @var list<array{name: string, reason: string, available: int|null, requested: int}>
     */
    public array $cartProblems = [];

    public function mount(): void
    {
        $customer = auth('customer')->user();
        if ($customer !== null) {
            $this->name = (string) $customer->name;
            $this->mobile = (string) $customer->mobile;
            $this->email = (string) ($customer->email ?? '');

            $default = $customer->addresses()->where('is_default', true)->first()
                ?? $customer->addresses()->first();
            if ($default !== null) {
                $this->addressChoice = (string) $default->id;
                $this->governorateId = $default->governorate_id;
            }
        }

        $first = $this->availableDeliveryMethods()->first();
        $this->deliveryMethodId = $first?->id;

        $this->cartProblems = $this->detectCartProblems();
    }

    /** Resolve the governorate that drives delivery filtering + the shipped-to address. */
    private function resolvedGovernorateId(): ?int
    {
        if ($this->addressChoice !== 'new') {
            return $this->selectedSavedAddress()?->governorate_id;
        }

        return $this->governorateId;
    }

    private function selectedSavedAddress(): ?CustomerAddress
    {
        $customer = auth('customer')->user();
        if ($customer === null || $this->addressChoice === 'new') {
            return null;
        }

        return $customer->addresses()->find((int) $this->addressChoice);
    }

    /** @return Collection<int, DeliveryMethod> */
    private function availableDeliveryMethods(): Collection
    {
        return app(CartManager::class)->availableDeliveryMethods($this->resolvedGovernorateId());
    }

    private function cardConfigured(): bool
    {
        return app(PaymobGateway::class)->isConfigured();
    }

    /** @return Collection<int, PaymentMethod> */
    private function availablePaymentMethods(): Collection
    {
        return PaymentMethod::active()
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (PaymentMethod $m) => $m->code !== PaymentMethodCode::Card || $this->cardConfigured())
            ->values();
    }

    /** Keep the selected delivery method valid as the governorate/address changes. */
    public function updated(string $property, mixed $value = null): void
    {
        if (in_array($property, ['governorateId', 'addressChoice'], true)) {
            $available = $this->availableDeliveryMethods();
            if (! $available->contains('id', $this->deliveryMethodId)) {
                $this->deliveryMethodId = $available->first()?->id;
            }
        }
    }

    public function place(PlaceOrder $placeOrder): void
    {
        // Cap order placement to 10 attempts / 10 min per IP.
        $key = 'checkout.'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->cartError = __('ui.too_many_attempts');
            $this->cartErrorShowLink = false;

            return;
        }
        RateLimiter::hit($key, 600);

        $this->mobile = preg_replace('/\s+/', '', $this->mobile) ?? $this->mobile;

        $available = $this->availableDeliveryMethods();
        $delivery = $available->firstWhere('id', $this->deliveryMethodId);
        $payment = $this->availablePaymentMethods()
            ->first(fn (PaymentMethod $m) => $m->code->value === $this->paymentCode);

        $needsAddress = $delivery?->requires_address === true;

        $this->validateCheckout($delivery, $payment, $needsAddress);

        /** @var DeliveryMethod $delivery */
        /** @var PaymentMethod $payment */
        $address = $needsAddress ? $this->buildAddress() : null;

        try {
            $order = $placeOrder->handle($delivery, $payment, [
                'name' => $this->name,
                'mobile' => $this->mobile,
                'email' => $this->email !== '' ? $this->email : null,
                'is_company' => $this->isCompany,
                'company_tax_id' => $this->companyTaxId !== '' ? $this->companyTaxId : null,
            ], $address);
        } catch (CheckoutException $e) {
            $this->cartError = match ($e->reason) {
                'out_of_stock' => __('ui.checkout_item_out_of_stock_named', ['name' => $e->itemName]),
                'insufficient_qty' => __('ui.checkout_item_insufficient_named', [
                    'name' => $e->itemName,
                    'requested' => $e->requested,
                    'available' => $e->available,
                ]),
                default => __($e->getMessage()),
            };
            $this->cartErrorShowLink = true;

            return;
        }

        if ($needsAddress && $this->saveAddress && $this->addressChoice === 'new' && auth('customer')->check()) {
            $this->persistAddress();
        }

        session()->push('orders.placed', $order->id);

        // Card orders detour to Paymob's hosted checkout; the gateway settles
        // the order via webhook/return. Everything else lands on confirmation.
        if ($payment->code === PaymentMethodCode::Card) {
            try {
                $url = app(PaymentGateway::class)->createCheckout($order);
            } catch (PaymentException $e) {
                $this->redirectRoute('order.payment-failed', [app()->getLocale(), $order->number], navigate: true);

                return;
            }

            $this->redirect($url);

            return;
        }

        $this->redirectRoute('order.confirmation', [app()->getLocale(), $order->number], navigate: true);
    }

    /** @return array{recipient_name: string, mobile: string, governorate_id: ?int, governorate_name: array<string, string>, city: string, street: string, floor_apartment: ?string, landmark: ?string} */
    private function buildAddress(): array
    {
        $saved = $this->selectedSavedAddress();
        if ($saved !== null) {
            $gov = $saved->governorate;

            return [
                'recipient_name' => $saved->recipient_name,
                'mobile' => $saved->mobile,
                'governorate_id' => $saved->governorate_id,
                'governorate_name' => $gov?->getTranslations('name') ?? [],
                'city' => $saved->city,
                'street' => $saved->street,
                'floor_apartment' => $saved->floor_apartment,
                'landmark' => $saved->landmark,
            ];
        }

        $gov = $this->governorateId !== null ? Governorate::find($this->governorateId) : null;

        return [
            'recipient_name' => $this->name,
            'mobile' => $this->mobile,
            'governorate_id' => $this->governorateId,
            'governorate_name' => $gov?->getTranslations('name') ?? [],
            'city' => $this->city,
            'street' => $this->street,
            'floor_apartment' => $this->floorApartment !== '' ? $this->floorApartment : null,
            'landmark' => $this->landmark !== '' ? $this->landmark : null,
        ];
    }

    private function persistAddress(): void
    {
        $customer = auth('customer')->user();
        if ($customer === null || $this->governorateId === null) {
            return;
        }

        if ($customer->addresses()->count() >= CustomerAddress::MAX_PER_CUSTOMER) {
            return;
        }

        $customer->addresses()->create([
            'recipient_name' => $this->name,
            'mobile' => $this->mobile,
            'governorate_id' => $this->governorateId,
            'city' => $this->city,
            'street' => $this->street,
            'floor_apartment' => $this->floorApartment !== '' ? $this->floorApartment : null,
            'landmark' => $this->landmark !== '' ? $this->landmark : null,
            'is_default' => $customer->addresses()->count() === 0,
        ]);
    }

    private function validateCheckout(?DeliveryMethod $delivery, ?PaymentMethod $payment, bool $needsAddress): void
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'regex:/^01[0125]\d{8}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'companyTaxId' => [$this->isCompany ? 'required' : 'nullable', 'string', 'max:50'],
            'deliveryMethodId' => ['required'],
            'paymentCode' => ['required'],
        ];

        if ($needsAddress && $this->addressChoice === 'new') {
            $rules += [
                'governorateId' => ['required', 'integer', 'exists:governorates,id'],
                'city' => ['required', 'string', 'max:120'],
                'street' => ['required', 'string', 'max:200'],
                'floorApartment' => ['nullable', 'string', 'max:60'],
                'landmark' => ['nullable', 'string', 'max:120'],
            ];
        }

        $this->validate($rules, [
            'mobile.regex' => __('ui.mobile_invalid'),
        ]);

        if ($delivery === null) {
            throw ValidationException::withMessages(['deliveryMethodId' => __('ui.checkout_delivery_required')]);
        }
        if ($payment === null) {
            throw ValidationException::withMessages(['paymentCode' => __('ui.checkout_payment_required')]);
        }
        if ($needsAddress && $this->addressChoice !== 'new' && $this->selectedSavedAddress() === null) {
            throw ValidationException::withMessages(['addressChoice' => __('ui.checkout_address_required')]);
        }
    }

    /**
     * Scans each cart line against current stock and returns problem descriptors.
     * Mirrors the gate in PlaceOrder::createItems() so the customer sees issues
     * before filling the form, not only on submit.
     *
     * @return list<array{name: string, reason: string, available: int|null, requested: int}>
     */
    private function detectCartProblems(): array
    {
        $problems = [];
        $locale = app()->getLocale();

        foreach (app(CartManager::class)->items() as $item) {
            $variant = $item->productVariant;
            $product = $variant->product;
            $name = $product->getTranslation('name', $locale);
            $stock = (int) $variant->stock_qty;

            if ($stock === 0) {
                $problems[] = ['name' => $name, 'reason' => 'out_of_stock', 'available' => 0, 'requested' => (int) $item->qty];
            } elseif ($stock < $item->qty) {
                $problems[] = ['name' => $name, 'reason' => 'insufficient_qty', 'available' => $stock, 'requested' => (int) $item->qty];
            }
        }

        return $problems;
    }

    public function render(): View
    {
        $cart = app(CartManager::class);
        $delivery = $this->availableDeliveryMethods()->firstWhere('id', $this->deliveryMethodId);

        return view('livewire.shop.checkout-form', [
            'items' => $cart->items(),
            'totals' => $cart->totalsForDelivery($delivery),
            'deliveryMethods' => $this->availableDeliveryMethods(),
            'paymentMethods' => $this->availablePaymentMethods(),
            'governorates' => Governorate::active()->orderBy('sort_order')->get(),
            'savedAddresses' => auth('customer')->user()?->addresses()->with('governorate')->get() ?? collect(),
            'locale' => app()->getLocale(),
        ]);
    }
}

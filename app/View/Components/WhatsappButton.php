<?php

namespace App\View\Components;

use App\Enums\QuoteSource;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;
use Illuminate\View\Component;

class WhatsappButton extends Component
{
    public string $href;

    /**
     * @param  string  $source  QuoteSource value
     * @param  Model|null  $model  Related Eloquent model (Product, Brand, Service …)
     * @param  string  $label  Override button label (defaults to locale lang key)
     * @param  string  $class  Extra CSS classes
     */
    public function __construct(
        public string $source,
        public ?Model $model = null,
        public ?ProductVariant $variant = null,
        public ?int $qty = null,
        public string $label = '',
        public string $class = '',
        public bool $iconOnly = false,
    ) {
        $locale = app()->getLocale();

        $params = ['locale' => $locale, 'source' => $source];

        if ($model !== null) {
            $params['model_type'] = $model->getMorphClass();
            $params['model_id'] = $model->getKey();
        }

        if ($variant !== null) {
            $params['variant_id'] = $variant->getKey();
            $params['sku'] = $variant->sku ?? '';
            // product_name for template substitution
            $params['product_name'] = $variant->product->name ?? '';
        }

        if ($qty !== null) {
            $params['qty'] = $qty;
        }

        $params['ref_url'] = request()->url();

        $this->href = URL::signedRoute('wa.redirect', $params);
    }

    public function render(): View
    {
        return view('components.whatsapp-button');
    }
}

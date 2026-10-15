<?php

namespace App\Livewire\Shop;

use App\Enums\StockStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;

class VariantPicker extends Component
{
    public int $productId;

    public int $qty = 1;

    /** attribute_id => value_id
     *
     * @var array<int, int>
     */
    public array $selected = [];

    public function mount(Product $product): void
    {
        $this->productId = $product->id;

        $variants = $product->variants()
            ->active()
            ->with(['attributeValues.attribute'])
            ->orderBy('sort_order')
            ->get();

        $default = $variants->firstWhere('is_default', true) ?? $variants->first();

        if ($default) {
            foreach ($default->attributeValues as $av) {
                $this->selected[$av->attribute_id] = $av->id;
            }
        }
    }

    public function select(int $attrId, int $valueId): void
    {
        // Guard: only allow values that are not disabled
        $variantData = $this->variantDataFor($this->productId);
        if (! $this->isDisabled($attrId, $valueId, $variantData)) {
            $this->selected[$attrId] = $valueId;
        }
    }

    public function incrementQty(): void
    {
        $this->qty = min(99, $this->qty + 1);
    }

    public function decrementQty(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        $product = Product::with([
            'brand',
            'variants' => fn ($q) => $q->active()->orderBy('sort_order')
                ->with(['attributeValues.attribute']),
        ])->findOrFail($this->productId);

        $variantData = $this->buildVariantData($product->variants);
        $axes = $this->buildAxes($product->variants, $locale);

        // Disabled value IDs given current selection
        $disabled = [];
        foreach ($axes as $axis) {
            foreach ($axis['values'] as $val) {
                if ($this->isDisabled($axis['id'], $val['id'], $variantData)) {
                    $disabled[] = $val['id'];
                }
            }
        }

        $selectedVariant = $this->findSelectedVariant($variantData, $product->variants);

        if ($selectedVariant && ! $selectedVariant->relationLoaded('product')) {
            $selectedVariant->setRelation('product', $product);
        }

        // Effective price / stock from selected variant or product cache
        $hasPrice = false;
        $price = null;
        $compareAt = null;
        $sku = null;
        $stockStatus = null;
        $stockQty = null;

        if ($selectedVariant) {
            $hasPrice = ! $product->price_on_request && $selectedVariant->price !== null;
            $price = $selectedVariant->price;
            $compareAt = $selectedVariant->compare_at_price;
            $sku = $selectedVariant->sku;
            $stockStatus = $this->variantStockStatus($selectedVariant);
            $stockQty = $selectedVariant->stock_qty;
        } else {
            // No variants or no match — fall back to product-level cache columns
            $hasPrice = ! $product->price_on_request && $product->min_price !== null;
            $price = $product->min_price;
            $compareAt = $product->max_compare_at;
            $sku = $product->sku ?? null;
            $stockStatus = $product->stock_status;
            $stockQty = null;
        }

        $canAddToCart = $selectedVariant !== null
            && $hasPrice
            && $stockStatus !== StockStatus::Order;

        return view('livewire.shop.variant-picker', compact(
            'product',
            'axes',
            'disabled',
            'selectedVariant',
            'hasPrice',
            'price',
            'compareAt',
            'sku',
            'stockStatus',
            'stockQty',
            'canAddToCart',
        ));
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return list<array{id:int,price:mixed,compare_at_price:mixed,sku:string|null,stock_qty:int,valueIds:int[]}>
     */
    private function buildVariantData($variants): array
    {
        return $variants->map(fn (ProductVariant $v) => [
            'id' => $v->id,
            'price' => $v->price,
            'compare_at_price' => $v->compare_at_price,
            'sku' => $v->sku,
            'stock_qty' => $v->stock_qty,
            'valueIds' => $v->attributeValues->pluck('id')->map(fn ($id) => (int) $id)->all(),
        ])->all();
    }

    /**
     * @return array<int, array{id:int,price:mixed,compare_at_price:mixed,sku:string|null,stock_qty:int,valueIds:int[]}>
     */
    private function variantDataFor(int $productId): array
    {
        $variants = ProductVariant::where('product_id', $productId)
            ->where('is_active', true)
            ->with(['attributeValues'])
            ->get();

        return $this->buildVariantData($variants);
    }

    /** Derives StockStatus from variant stock_qty and low_stock_threshold. */
    private function variantStockStatus(ProductVariant $v): StockStatus
    {
        $threshold = $v->low_stock_threshold ?? 5;

        if ($v->stock_qty > $threshold) {
            return StockStatus::In;
        }

        if ($v->stock_qty > 0) {
            return StockStatus::Low;
        }

        return StockStatus::Order;
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return list<array{id:int,name:string,display:string,values:list<array{id:int,label:string,swatch:string|null,sort_order:int}>}>
     */
    private function buildAxes($variants, string $locale): array
    {
        $axesMap = [];

        foreach ($variants as $variant) {
            foreach ($variant->attributeValues as $av) {
                $attr = $av->attribute;
                $attrId = (int) $attr->id;

                if (! isset($axesMap[$attrId])) {
                    $axesMap[$attrId] = [
                        'id' => $attrId,
                        'name' => $attr->getTranslation('name', $locale),
                        'display' => $attr->display->value,
                        'values' => [],
                    ];
                }

                $valId = (int) $av->id;
                if (! isset($axesMap[$attrId]['values'][$valId])) {
                    $axesMap[$attrId]['values'][$valId] = [
                        'id' => $valId,
                        'label' => $av->getTranslation('label', $locale),
                        'swatch' => $av->swatch,
                        'sort_order' => $av->sort_order ?? 0,
                    ];
                }
            }
        }

        return array_values(array_map(function (array $axis): array {
            $values = array_values($axis['values']);
            usort($values, fn ($a, $b) => $a['sort_order'] <=> $b['sort_order']);
            $axis['values'] = $values;

            return $axis;
        }, $axesMap));
    }

    /**
     * A value is disabled when no active variant can satisfy the combination of
     * the candidate value + all currently-selected values for OTHER attributes.
     *
     * @param  array<int, array{valueIds: int[]}>  $variantData
     */
    private function isDisabled(int $attrId, int $valueId, array $variantData): bool
    {
        // Other selections (excluding the attribute we're checking)
        $otherSelected = array_filter(
            $this->selected,
            fn ($k) => $k !== $attrId,
            ARRAY_FILTER_USE_KEY,
        );

        foreach ($variantData as $v) {
            if (! in_array($valueId, $v['valueIds'], true)) {
                continue;
            }

            $satisfies = true;
            foreach ($otherSelected as $otherVid) {
                if (! in_array((int) $otherVid, $v['valueIds'], true)) {
                    $satisfies = false;
                    break;
                }
            }

            if ($satisfies) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, array{id:int,valueIds:int[]}>  $variantData
     * @param  Collection<int, ProductVariant>  $variants
     */
    private function findSelectedVariant(array $variantData, $variants): ?ProductVariant
    {
        if (empty($this->selected)) {
            return null;
        }

        foreach ($variantData as $v) {
            $matches = true;
            foreach ($this->selected as $valueId) {
                if (! in_array((int) $valueId, $v['valueIds'], true)) {
                    $matches = false;
                    break;
                }
            }

            if ($matches) {
                return $variants->find($v['id']);
            }
        }

        return null;
    }
}

<?php

namespace App\Livewire\Shop;

use App\Enums\StockStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\CacheTags;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductListing extends Component
{
    use WithPagination;

    /** Category filter — category ID (includes all descendants in query). */
    #[Url(as: 'cat', except: null)]
    public ?int $categoryId = null;

    /** Selected brand IDs.
     *
     * @var int[]
     */
    #[Url(as: 'brands', except: [])]
    public array $brandIds = [];

    /** Selected attribute_value IDs (multiple attributes allowed).
     *
     * @var int[]
     */
    #[Url(as: 'attrs', except: [])]
    public array $attrValueIds = [];

    /** Price range — EGP. */
    #[Url(as: 'min', except: null)]
    public ?int $priceMin = null;

    #[Url(as: 'max', except: null)]
    public ?int $priceMax = null;

    /** Availability toggles. */
    #[Url(as: 'instock', except: false)]
    public bool $inStock = false;

    #[Url(as: 'fast', except: false)]
    public bool $ships24h = false;

    /** Sort order key. */
    #[Url(as: 'sort', except: 'recommended')]
    public string $sort = 'recommended';

    /** Grid or list view. */
    #[Url(as: 'view', except: 'grid')]
    public string $view = 'grid';

    /** Brand search box value (sidebar only, not URL-bound). */
    public string $brandSearch = '';

    /** Show all brands in sidebar (default: first 5). */
    public bool $showAllBrands = false;

    // ─── Page reset on filter change ─────────────────────────────────────────

    public function updatedCategoryId(): void
    {
        $this->resetPage();
    }

    public function updatedBrandIds(): void
    {
        $this->resetPage();
    }

    public function updatedAttrValueIds(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMin(): void
    {
        $this->resetPage();
    }

    public function updatedPriceMax(): void
    {
        $this->resetPage();
    }

    public function updatedInStock(): void
    {
        $this->resetPage();
    }

    public function updatedShips24h(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    // ─── Actions ─────────────────────────────────────────────────────────────

    public function toggleBrand(int $id): void
    {
        if (in_array($id, $this->brandIds, true)) {
            $this->brandIds = array_values(array_filter($this->brandIds, fn ($b) => $b !== $id));
        } else {
            $this->brandIds[] = $id;
        }
        $this->resetPage();
    }

    public function toggleAttrValue(int $id): void
    {
        if (in_array($id, $this->attrValueIds, true)) {
            $this->attrValueIds = array_values(array_filter($this->attrValueIds, fn ($v) => $v !== $id));
        } else {
            $this->attrValueIds[] = $id;
        }
        $this->resetPage();
    }

    public function applyPriceRange(?int $min, ?int $max): void
    {
        $this->priceMin = $min ?: null;
        $this->priceMax = $max ?: null;
        $this->resetPage();
    }

    public function clearAll(): void
    {
        $this->brandIds = [];
        $this->attrValueIds = [];
        $this->priceMin = null;
        $this->priceMax = null;
        $this->inStock = false;
        $this->ships24h = false;
        $this->resetPage();
    }

    public function activeFilterCount(): int
    {
        return count($this->brandIds)
            + count($this->attrValueIds)
            + ($this->inStock ? 1 : 0)
            + ($this->ships24h ? 1 : 0)
            + ($this->priceMin !== null || $this->priceMax !== null ? 1 : 0);
    }

    public function paginationView(): string
    {
        return 'livewire.admin-pagination';
    }

    // ─── Render ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        $products = $this->buildQuery()->with(['media', 'brand'])->paginate(12);
        $facets = $this->getFacets();
        $catTree = $this->getCategoryTree();
        $activeCount = $this->activeFilterCount();
        $currentCat = $this->categoryId ? Category::find($this->categoryId) : null;

        return view('livewire.shop.product-listing', compact(
            'products', 'facets', 'catTree', 'activeCount', 'currentCat'
        ));
    }

    // ─── Query builder ───────────────────────────────────────────────────────

    /**
     * Build the base product query with optional filter exclusions.
     * When $sort = false, no ORDER BY is applied (for aggregate queries).
     *
     * @param  string[]  $except  filter keys to skip: 'category'|'brands'|'attrs'
     * @return Builder<Product>
     */
    public function buildQuery(array $except = [], bool $sort = true): Builder
    {
        $q = Product::active();

        if ($this->categoryId && ! in_array('category', $except)) {
            $q->whereIn('category_id', $this->categoryScope($this->categoryId));
        }

        if ($this->brandIds && ! in_array('brands', $except)) {
            $q->whereIn('brand_id', $this->brandIds);
        }

        if ($this->attrValueIds && ! in_array('attrs', $except)) {
            $this->applyAttrFilter($q);
        }

        if ($this->priceMin !== null) {
            $q->where('min_price', '>=', $this->priceMin);
        }

        if ($this->priceMax !== null) {
            $q->where('min_price', '<=', $this->priceMax);
        }

        if ($this->inStock) {
            $q->whereIn('stock_status', [StockStatus::In->value, StockStatus::Low->value]);
        }

        if ($this->ships24h) {
            $q->where('ships_24h', true);
        }

        return $sort ? $this->applySort($q) : $q;
    }

    /** @param  Builder<Product>  $q
     * @return Builder<Product>
     */
    protected function applySort(Builder $q): Builder
    {
        return match ($this->sort) {
            'price_asc' => $q->orderBy('min_price')->orderBy('products.id'),
            'price_desc' => $q->orderByDesc('min_price')->orderByDesc('products.id'),
            'newest' => $q->orderByDesc('published_at')->orderByDesc('products.id'),
            default => $q->orderByDesc('sort_weight')->orderByDesc('is_best_seller')->orderBy('products.id'),
        };
    }

    /** Adds a whereHas per selected attribute (AND-semantics across attributes).
     *
     * @param  Builder<Product>  $q
     */
    protected function applyAttrFilter(Builder $q): void
    {
        foreach ($this->attrValueGrouped() as $attrId => $valueIds) {
            $q->whereHas('variants', function (Builder $vq) use ($valueIds): void {
                $vq->where('is_active', true)
                    ->whereHas('attributeValues', function (Builder $avq) use ($valueIds): void {
                        $avq->whereIn('attribute_values.id', $valueIds);
                    });
            });
        }
    }

    /**
     * Groups selected attrValueIds by their parent attribute_id.
     *
     * @return array<int, int[]> attribute_id => [value_id, ...]
     */
    protected function attrValueGrouped(): array
    {
        if (empty($this->attrValueIds)) {
            return [];
        }

        return AttributeValue::whereIn('id', $this->attrValueIds)
            ->get(['id', 'attribute_id'])
            ->groupBy('attribute_id')
            ->map(fn ($vals) => $vals->pluck('id')->all())
            ->all();
    }

    /**
     * Category ID + all descendants (levels 2 and 3 included).
     * Cached so the recursion only runs once per category per TTL.
     *
     * @return int[]
     */
    protected function categoryScope(int $catId): array
    {
        return CacheTags::remember(
            CacheTags::CATEGORIES,
            "cat_scope.{$catId}",
            3600,
            function () use ($catId): array {
                $level2 = Category::where('parent_id', $catId)->pluck('id');
                $level3 = Category::whereIn('parent_id', $level2)->pluck('id');

                return $level2->merge($level3)->prepend($catId)->unique()->values()->all();
            }
        );
    }

    // ─── Facets (cached) ─────────────────────────────────────────────────────

    /**
     * Returns brand + attribute facet counts, cached by a hash of the current
     * filter state. Brand counts exclude the brand filter (isolation); attribute
     * counts exclude all attribute filters.
     *
     * @return array{brands: array<int, array{id:int,name:string,count:int}>, attributes: list<array{id:int,name:string,display:string,values:list<array{id:int,label:string,swatch:string|null,count:int}>}>}
     */
    protected function getFacets(): array
    {
        $locale = app()->getLocale();
        $hash = $this->filterHash();

        return CacheTags::remember(
            CacheTags::FACETS,
            "facets.{$locale}.{$hash}",
            120,
            function () use ($locale): array {
                // ── Brand counts (exclude active brand filter for correct UX) ──
                $brandRaw = $this->buildQuery(['brands'], false)
                    ->whereNotNull('brand_id')
                    ->groupBy('brand_id')
                    ->selectRaw('brand_id, COUNT(*) as cnt')
                    ->pluck('cnt', 'brand_id')
                    ->all();

                $brands = Brand::active()
                    ->orderBy('name')
                    ->get(['id', 'name'])
                    ->map(fn ($b) => [
                        'id' => $b->id,
                        'name' => $b->name,
                        'count' => (int) ($brandRaw[$b->id] ?? 0),
                    ])
                    ->filter(fn ($b) => $b['count'] > 0 || in_array($b['id'], $this->brandIds))
                    ->values()
                    ->all();

                // ── Attribute value counts (exclude all attr filters) ──
                $attrSubquery = $this->buildQuery(['attrs'], false)->select('products.id');
                $attrRaw = DB::table('product_variant_values as pvv')
                    ->join('product_variants as pv', 'pv.id', '=', 'pvv.product_variant_id')
                    ->whereIn('pv.product_id', $attrSubquery)
                    ->where('pv.is_active', true)
                    ->groupBy('pvv.attribute_value_id')
                    ->selectRaw('pvv.attribute_value_id, COUNT(DISTINCT pv.product_id) as cnt')
                    ->pluck('cnt', 'attribute_value_id')
                    ->all();

                $attributes = Attribute::where('is_filterable', true)
                    ->orderBy('sort_order')
                    ->with(['values' => fn ($q) => $q->orderBy('sort_order')])
                    ->get()
                    ->map(fn ($attr) => [
                        'id' => $attr->id,
                        'name' => $attr->getTranslation('name', $locale),
                        'display' => $attr->display->value,
                        'values' => collect($attr->values)
                            ->map(fn ($v) => [
                                'id' => $v->id,
                                'label' => $v->getTranslation('label', $locale),
                                'swatch' => $v->swatch,
                                'count' => (int) ($attrRaw[$v->id] ?? 0),
                            ])
                            ->filter(fn ($v) => $v['count'] > 0 || in_array($v['id'], $this->attrValueIds))
                            ->values()
                            ->all(),
                    ])
                    ->filter(fn ($a) => count($a['values']) > 0)
                    ->values()
                    ->all();

                return compact('brands', 'attributes');
            }
        );
    }

    /** Full category tree (level 1 → 2 → 3) for the sidebar.
     *
     * @return Collection<int, Category>
     */
    protected function getCategoryTree(): Collection
    {
        return CacheTags::remember(
            CacheTags::CATEGORIES,
            'listing.cattree',
            3600,
            fn () => Category::active()
                ->whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->active()
                    ->orderBy('sort_order')
                    ->with(['children' => fn ($q2) => $q2->active()->orderBy('sort_order')])])
                ->orderBy('sort_order')
                ->get()
        );
    }

    /** MD5 hash of all filter state — used as the facet cache key suffix. */
    protected function filterHash(): string
    {
        $brands = $this->brandIds;
        $attrs = $this->attrValueIds;
        sort($brands);
        sort($attrs);

        return md5((string) json_encode([
            'cat' => $this->categoryId,
            'brands' => $brands,
            'attrs' => $attrs,
            'min' => $this->priceMin,
            'max' => $this->priceMax,
            'instock' => $this->inStock,
            'fast' => $this->ships24h,
        ]));
    }
}

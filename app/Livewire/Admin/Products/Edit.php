<?php

namespace App\Livewire\Admin\Products;

use App\Actions\UpdateProductCacheColumns;
use App\Enums\ProductStatus;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Html;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use WithFileUploads;

    public ?Product $product = null;

    public string $activeTab = 'basic';

    // Basic
    public string $sku = '';

    /** @var array<string, string> */
    public array $name = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $overview = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $variants_summary = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $warranty = ['ar' => '', 'en' => ''];

    // Category & brand
    public ?int $brand_id = null;

    public ?int $category_id = null;

    // Variants
    public bool $price_on_request = false;

    /** @var array<int, array{attribute_id: int, values: array<int, int>}> */
    public array $axes = [];

    /** @var array<string, array{id: int|null, sku: string, price: string, compare_at_price: string, stock_qty: int, low_stock_threshold: string, is_active: bool, value_ids: array<int, int>}> */
    public array $variantRows = [];

    public ?string $defaultKey = null;

    public string $newAxis = '';

    // Specs
    /** @var array<string, array{id: int|null, name: array<string,string>, value: array<string,string>}> */
    public array $specs = [];

    // Related
    /** @var array<int, int> */
    public array $relatedIds = [];

    /** @var array<int, int> */
    public array $cartSuggestionIds = [];

    // Flags
    public bool $is_new = false;

    public bool $is_best_seller = false;

    public bool $is_smart_pick = false;

    public bool $is_dimmable = false;

    public bool $ships_24h = false;

    public int $sort_weight = 50;

    // SEO + visibility
    /** @var array<string, string> */
    public array $slug = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['ar' => '', 'en' => ''];

    public string $status = 'draft';

    // Images
    /** @var array<int, mixed> */
    public array $galleryUploads = [];

    /** @var array<int, int> */
    public array $galleryOrder = [];

    /** @var array<int, array<string, string>> */
    public array $captions = [];

    /** @var array<int, mixed> */
    public array $fileUploads = [];

    public function mount(?Product $product = null): void
    {
        if ($product?->exists) {
            // Ensure cast attributes (status, flags) reflect stored defaults.
            $product->refresh()->load(['variants.attributeValues', 'specs', 'relatedProducts', 'cartSuggestions']);
            $this->product = $product;
            $this->sku = $product->sku;
            foreach (['name', 'overview', 'variants_summary', 'warranty', 'slug', 'meta_title', 'meta_description'] as $f) {
                $this->{$f} = ['ar' => $product->getTranslation($f, 'ar', false), 'en' => $product->getTranslation($f, 'en', false)];
            }
            $this->brand_id = $product->brand_id;
            $this->category_id = $product->category_id;
            $this->price_on_request = (bool) $product->price_on_request;
            $this->is_new = (bool) $product->is_new;
            $this->is_best_seller = (bool) $product->is_best_seller;
            $this->is_smart_pick = (bool) $product->is_smart_pick;
            $this->is_dimmable = (bool) $product->is_dimmable;
            $this->ships_24h = (bool) $product->ships_24h;
            $this->sort_weight = (int) $product->sort_weight;
            $this->status = $product->status->value;
            $this->relatedIds = $product->relatedProducts->pluck('id')->map(fn ($i) => (int) $i)->all();
            $this->cartSuggestionIds = $product->cartSuggestions->pluck('id')->map(fn ($i) => (int) $i)->all();

            foreach ($product->specs as $spec) {
                $this->specs[(string) Str::uuid()] = [
                    'id' => $spec->id,
                    'name' => ['ar' => $spec->getTranslation('name', 'ar', false), 'en' => $spec->getTranslation('name', 'en', false)],
                    'value' => ['ar' => $spec->getTranslation('value', 'ar', false), 'en' => $spec->getTranslation('value', 'en', false)],
                ];
            }

            $this->loadVariants($product);

            foreach ($product->getMedia('gallery') as $media) {
                $caption = $media->getCustomProperty('caption');
                $this->captions[$media->id] = is_array($caption)
                    ? ['ar' => (string) ($caption['ar'] ?? ''), 'en' => (string) ($caption['en'] ?? '')]
                    : ['ar' => '', 'en' => (string) ($caption ?? '')];
            }
        } else {
            $this->variantRows = ['default' => $this->blankVariant([])];
            $this->defaultKey = 'default';
        }
    }

    private function loadVariants(Product $product): void
    {
        // Rebuild the axes from the attribute values used by the variants.
        $axisValues = [];
        foreach ($product->variants as $variant) {
            foreach ($variant->attributeValues as $value) {
                $axisValues[$value->attribute_id][$value->id] = true;
            }
        }
        $this->axes = [];
        foreach ($axisValues as $attributeId => $values) {
            $this->axes[] = ['attribute_id' => (int) $attributeId, 'values' => array_map('intval', array_keys($values))];
        }

        foreach ($product->variants as $variant) {
            $ids = $variant->attributeValues->pluck('id')->map(fn ($i) => (int) $i)->sort()->values()->all();
            $key = $ids ? implode('-', $ids) : 'default';
            $this->variantRows[$key] = [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price === null ? '' : (string) $variant->price,
                'compare_at_price' => $variant->compare_at_price === null ? '' : (string) $variant->compare_at_price,
                'stock_qty' => (int) $variant->stock_qty,
                'low_stock_threshold' => $variant->low_stock_threshold === null ? '' : (string) $variant->low_stock_threshold,
                'is_active' => (bool) $variant->is_active,
                'value_ids' => $ids,
            ];
            if ($variant->is_default) {
                $this->defaultKey = $key;
            }
        }
        if ($this->variantRows === []) {
            $this->variantRows = ['default' => $this->blankVariant([])];
        }
        $this->defaultKey ??= array_key_first($this->variantRows);
    }

    /**
     * @param  array<int, int>  $valueIds
     * @return array{id: int|null, sku: string, price: string, compare_at_price: string, stock_qty: int, low_stock_threshold: string, is_active: bool, value_ids: array<int, int>}
     */
    private function blankVariant(array $valueIds): array
    {
        return [
            'id' => null,
            // The lone default variant (no axes) inherits the base SKU on save;
            // axis variants get a suggested SKU from their value codes.
            'sku' => $valueIds === [] ? '' : $this->suggestSku($valueIds),
            'price' => '',
            'compare_at_price' => '',
            'stock_qty' => 0,
            'low_stock_threshold' => '',
            'is_active' => true,
            'value_ids' => $valueIds,
        ];
    }

    /** @param  array<int, int>  $valueIds */
    private function suggestSku(array $valueIds): string
    {
        $base = $this->sku !== '' ? $this->sku : 'SKU';
        if ($valueIds === []) {
            return $base;
        }
        $codes = AttributeValue::whereIn('id', $valueIds)->pluck('value')->all();

        return $base.'-'.implode('-', array_map(fn ($c) => Str::upper(Str::substr((string) $c, 0, 3)), $codes));
    }

    public function addAxis(): void
    {
        $id = (int) $this->newAxis;
        if ($id && ! collect($this->axes)->contains('attribute_id', $id)) {
            $first = Attribute::with('values')->find($id)?->values->first();
            $this->axes[] = ['attribute_id' => $id, 'values' => $first ? [(int) $first->id] : []];
            $this->regenerateVariants();
        }
        $this->newAxis = '';
    }

    public function removeAxis(int $index): void
    {
        unset($this->axes[$index]);
        $this->axes = array_values($this->axes);
        $this->regenerateVariants();
    }

    public function toggleAxisValue(int $index, int $valueId): void
    {
        if (! isset($this->axes[$index])) {
            return;
        }
        $values = $this->axes[$index]['values'];
        $this->axes[$index]['values'] = in_array($valueId, $values, true)
            ? array_values(array_filter($values, fn ($v) => $v !== $valueId))
            : [...$values, $valueId];
        $this->regenerateVariants();
    }

    /** Rebuild the variant rows from the cartesian product of the axis values. */
    public function regenerateVariants(): void
    {
        $combos = [[]];
        foreach ($this->axes as $axis) {
            $values = $axis['values'];
            if ($values === []) {
                continue;
            }
            $next = [];
            foreach ($combos as $combo) {
                foreach ($values as $valueId) {
                    $next[] = [...$combo, (int) $valueId];
                }
            }
            $combos = $next;
        }

        $rebuilt = [];
        foreach ($combos as $ids) {
            sort($ids);
            $key = $ids ? implode('-', $ids) : 'default';
            $row = $this->variantRows[$key] ?? $this->blankVariant($ids);
            $row['value_ids'] = $ids;
            $rebuilt[$key] = $row;
        }
        $this->variantRows = $rebuilt;

        if (! isset($this->variantRows[$this->defaultKey])) {
            $this->defaultKey = array_key_first($this->variantRows);
        }
    }

    public function applyFirstPrice(): void
    {
        $first = reset($this->variantRows);
        if (! $first) {
            return;
        }
        foreach ($this->variantRows as $key => $row) {
            $this->variantRows[$key]['price'] = $first['price'];
            $this->variantRows[$key]['compare_at_price'] = $first['compare_at_price'];
        }
    }

    // --- Specs -------------------------------------------------------------

    public function addSpec(): void
    {
        $this->specs[(string) Str::uuid()] = ['id' => null, 'name' => ['ar' => '', 'en' => ''], 'value' => ['ar' => '', 'en' => '']];
    }

    public function removeSpec(string $key): void
    {
        unset($this->specs[$key]);
    }

    /** @param  array<int, string>  $keys */
    public function reorderSpecs(array $keys): void
    {
        $ordered = [];
        foreach ($keys as $key) {
            if (isset($this->specs[$key])) {
                $ordered[$key] = $this->specs[$key];
            }
        }
        $this->specs = $ordered;
    }

    public function save(): void
    {
        $this->authorize($this->product ? 'products.update' : 'products.create');

        $this->sku = trim($this->sku);

        // Auto-generate the per-locale slugs from the name when left blank, so
        // the admin only has to fill the visible required fields.
        foreach (['ar', 'en'] as $locale) {
            if (trim((string) ($this->slug[$locale] ?? '')) === '' && trim((string) ($this->name[$locale] ?? '')) !== '') {
                $this->slug[$locale] = Slug::make($this->name[$locale], $locale);
            }
        }

        // The lone default variant inherits the base SKU when its own is blank.
        if (count($this->variantRows) === 1 && isset($this->variantRows['default'])
            && trim((string) $this->variantRows['default']['sku']) === '') {
            $this->variantRows['default']['sku'] = $this->sku;
        }

        try {
            $this->validateProduct();
        } catch (ValidationException $e) {
            $this->focusErrors(array_keys($e->errors()));
            throw $e;
        }

        if (! $this->variantsAreValid()) {
            $this->focusErrors($this->getErrorBag()->keys());

            return;
        }

        $product = $this->product ?? new Product;
        $product->fill([
            'sku' => $this->sku,
            'name' => $this->name,
            'overview' => Html::cleanTranslations($this->overview),
            'variants_summary' => $this->variants_summary,
            'warranty' => $this->warranty,
            'slug' => $this->slug,
            'brand_id' => $this->brand_id,
            'category_id' => $this->category_id,
            'price_on_request' => $this->price_on_request,
            'is_new' => $this->is_new,
            'is_best_seller' => $this->is_best_seller,
            'is_smart_pick' => $this->is_smart_pick,
            'is_dimmable' => $this->is_dimmable,
            'ships_24h' => $this->ships_24h,
            'sort_weight' => $this->sort_weight,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'status' => $this->status,
            'published_at' => $this->status === ProductStatus::Active->value ? ($product->published_at ?? now()) : $product->published_at,
        ]);
        $product->save();

        $this->syncVariants($product);
        $this->syncSpecs($product);
        $this->syncRelations($product);
        $this->syncGallery($product);
        $this->syncFiles($product);

        // Recompute cache columns (min_price / stock_status / search_text) now
        // that variants exist — the observer runs per variant, but do it once more.
        app(UpdateProductCacheColumns::class)->handle($product->refresh());

        $this->product = $product;

        // Flash the success toast so it survives the redirect (the admin layout
        // pushes any session-flashed toast on load).
        session()->flash('admin.toast', [
            'tone' => 'success',
            'title' => __('admin.product_edit.saved'),
            'message' => $product->sku,
        ]);

        // After creating, land on the new product's edit page; after editing,
        // reload the same editor.
        $this->redirectRoute('admin.products.edit', $product, navigate: true);
    }

    /**
     * Open the tab holding the first validation error and raise an error toast
     * so failures on hidden tabs are never silent.
     *
     * @param  array<int, string>  $fields
     */
    private function focusErrors(array $fields): void
    {
        $first = $fields[0] ?? null;
        if ($first !== null) {
            $this->activeTab = $this->tabForField($first);
        }

        $this->dispatch('toast', tone: 'error', title: __('admin.product_edit.has_errors'));
    }

    private function tabForField(string $field): string
    {
        return match (true) {
            $field === 'sku',
            str_starts_with($field, 'name'),
            str_starts_with($field, 'overview'),
            str_starts_with($field, 'variants_summary'),
            str_starts_with($field, 'warranty') => 'basic',
            str_starts_with($field, 'brand_id'),
            str_starts_with($field, 'category_id') => 'cat',
            str_starts_with($field, 'variantRows'),
            $field === 'price_on_request' => 'var',
            str_starts_with($field, 'specs') => 'spec',
            str_starts_with($field, 'slug'),
            str_starts_with($field, 'meta_title'),
            str_starts_with($field, 'meta_description') => 'seo',
            $field === 'sort_weight' => 'flags',
            default => 'basic',
        };
    }

    /**
     * Tabs that currently hold a validation error (for the error markers).
     *
     * @return array<int, string>
     */
    public function errorTabs(): array
    {
        $tabs = [];
        foreach ($this->getErrorBag()->keys() as $field) {
            $tabs[$this->tabForField($field)] = true;
        }

        return array_keys($tabs);
    }

    public function deleteProduct(): void
    {
        $this->authorize('products.delete');
        $this->product?->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.products.deleted'));
        $this->redirectRoute('admin.products', navigate: true);
    }

    private function validateProduct(): void
    {
        $this->validate([
            'sku' => ['required', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($this->product?->id)],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'slug.ar' => ['required', 'string', 'max:255', Rule::unique('products', 'slug_ar')->ignore($this->product?->id)],
            'slug.en' => ['required', 'string', 'max:255', Rule::unique('products', 'slug_en')->ignore($this->product?->id)],
            'brand_id' => ['required', 'exists:brands,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'sort_weight' => ['integer', 'min:0'],
            'status' => ['required', Rule::enum(ProductStatus::class)],
        ], [
            'name.ar.required' => __('admin.product_edit.v_name_required'),
            'name.en.required' => __('admin.product_edit.v_name_required'),
            'sku.required' => __('admin.product_edit.v_sku_required'),
            'sku.unique' => __('admin.product_edit.v_sku_unique'),
            'slug.ar.unique' => __('admin.product_edit.v_slug_unique'),
            'slug.en.unique' => __('admin.product_edit.v_slug_unique'),
            'brand_id.required' => __('admin.product_edit.v_brand_required'),
            'brand_id.exists' => __('admin.product_edit.v_brand_required'),
            'category_id.required' => __('admin.product_edit.v_category_required'),
            'category_id.exists' => __('admin.product_edit.v_category_required'),
        ]);
    }

    /** Each variant needs a SKU, unique within the form and across the catalogue. */
    private function variantsAreValid(): bool
    {
        $ok = true;
        $skus = [];
        foreach ($this->variantRows as $key => $row) {
            $sku = trim((string) $row['sku']);
            if ($sku === '') {
                $this->addError("variantRows.$key.sku", __('admin.product_edit.variant_sku_required'));
                $ok = false;

                continue;
            }
            if (isset($skus[$sku])) {
                $this->addError("variantRows.$key.sku", __('admin.product_edit.variant_sku_duplicate'));
                $ok = false;

                continue;
            }
            $skus[$sku] = true;

            $exists = ProductVariant::where('sku', $sku)
                ->when($row['id'], fn ($q) => $q->whereKeyNot($row['id']))
                ->exists();
            if ($exists) {
                $this->addError("variantRows.$key.sku", __('admin.product_edit.variant_sku_duplicate'));
                $ok = false;
            }
        }

        return $ok;
    }

    private function syncVariants(Product $product): void
    {
        $keptIds = [];
        $order = 0;
        foreach ($this->variantRows as $key => $row) {
            $variant = $row['id']
                ? $product->variants()->whereKey($row['id'])->first() ?? $product->variants()->make()
                : $product->variants()->make();

            $variant->fill([
                'sku' => trim((string) $row['sku']),
                'price' => $row['price'] === '' ? null : $row['price'],
                'compare_at_price' => $row['compare_at_price'] === '' ? null : $row['compare_at_price'],
                'stock_qty' => (int) $row['stock_qty'],
                'low_stock_threshold' => $row['low_stock_threshold'] === '' ? null : (int) $row['low_stock_threshold'],
                'is_active' => (bool) $row['is_active'],
                'is_default' => $key === $this->defaultKey,
                'sort_order' => $order++,
            ]);
            $product->variants()->save($variant);
            $variant->attributeValues()->sync($row['value_ids']);
            $keptIds[] = $variant->id;
        }
        $product->variants()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    private function syncSpecs(Product $product): void
    {
        $keptIds = [];
        $order = 0;
        foreach ($this->specs as $row) {
            $spec = $row['id'] ? $product->specs()->whereKey($row['id'])->first() ?? $product->specs()->make() : $product->specs()->make();
            $spec->fill(['name' => $row['name'], 'value' => $row['value'], 'sort_order' => $order++]);
            $product->specs()->save($spec);
            $keptIds[] = $spec->id;
        }
        $product->specs()->whereNotIn('id', $keptIds ?: [0])->delete();
    }

    private function syncRelations(Product $product): void
    {
        // Each relation set is scoped by `type` on the pivot; detach then reattach
        // in order so sort_order reflects the chosen order.
        $product->relatedProducts()->detach();
        foreach (array_values($this->relatedIds) as $i => $id) {
            $product->relatedProducts()->attach((int) $id, ['type' => 'together', 'sort_order' => $i]);
        }

        $product->cartSuggestions()->detach();
        foreach (array_values($this->cartSuggestionIds) as $i => $id) {
            $product->cartSuggestions()->attach((int) $id, ['type' => 'cart_suggestion', 'sort_order' => $i]);
        }
    }

    public function removeImage(int $mediaId): void
    {
        $this->product?->media()->where('collection_name', 'gallery')->whereKey($mediaId)->first()?->delete();
    }

    /** @param  array<int, int|string>  $ids */
    public function reorderGallery(array $ids): void
    {
        Media::setNewOrder(array_map('intval', $ids));
    }

    private function syncGallery(Product $product): void
    {
        foreach ($this->galleryUploads as $upload) {
            if ($upload instanceof TemporaryUploadedFile) {
                $product->addMedia($upload->getRealPath())
                    ->usingFileName($upload->getClientOriginalName())
                    ->toMediaCollection('gallery');
            }
        }
        $this->galleryUploads = [];

        // Persist captions ({ar, en}) onto the media custom properties.
        foreach ($product->getMedia('gallery') as $media) {
            if (array_key_exists($media->id, $this->captions)) {
                $media->setCustomProperty('caption', [
                    'ar' => (string) ($this->captions[$media->id]['ar'] ?? ''),
                    'en' => (string) ($this->captions[$media->id]['en'] ?? ''),
                ]);
                $media->save();
            }
        }
    }

    public function removeFile(int $mediaId): void
    {
        $this->product?->media()->where('collection_name', 'files')->whereKey($mediaId)->first()?->delete();
    }

    private function syncFiles(Product $product): void
    {
        foreach ($this->fileUploads as $upload) {
            if ($upload instanceof TemporaryUploadedFile) {
                $product->addMedia($upload->getRealPath())
                    ->usingFileName($upload->getClientOriginalName())
                    ->toMediaCollection('files');
            }
        }
        $this->fileUploads = [];
    }

    /** @return array<int, array{id: int, name: string}> */
    public function fileItems(): array
    {
        if (! $this->product) {
            return [];
        }

        return $this->product->getMedia('files')
            ->map(fn ($media) => ['id' => (int) $media->id, 'name' => $media->file_name])->all();
    }

    /** @return array<int, array{id: int, url: string}> */
    public function galleryItems(): array
    {
        if (! $this->product) {
            return [];
        }

        return $this->product->getMedia('gallery')
            ->map(fn ($media) => [
                'id' => (int) $media->id,
                'url' => $media->getUrl(),
            ])->all();
    }

    public function render(): View
    {
        $attributes = Attribute::query()->where('is_variant_axis', true)->with('values')->orderBy('sort_order')->get();

        return view('livewire.admin.products.edit', [
            'axisAttributes' => $attributes,
            'brands' => Brand::orderBy('name')->get(),
            'leaves' => Category::query()->where('level', 3)->with('parent.parent')->orderBy('sort_order')->get(),
            'galleryItems' => $this->galleryItems(),
            'fileItems' => $this->fileItems(),
            'errorTabs' => $this->errorTabs(),
        ]);
    }
}

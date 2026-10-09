<?php

namespace App\Livewire\Admin\Products;

use App\Enums\ProductStatus;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    #[Url(except: 'all', history: false)]
    public string $tab = 'all';

    #[Url(except: '', history: false)]
    public string $category = '';

    #[Url(except: '', history: false)]
    public string $brand = '';

    #[Url(except: '', history: false)]
    public string $stock = '';

    public function updatingTab(): void
    {
        $this->resetPage();
        $this->selected = [];
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingBrand(): void
    {
        $this->resetPage();
    }

    public function updatingStock(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'brand', 'stock', 'tab');
        $this->resetPage();
    }

    // --- Bulk actions ------------------------------------------------------

    public function bulkPublish(): void
    {
        $this->authorize('products.publish');
        Product::whereIn('id', $this->selected)->update(['status' => ProductStatus::Active->value, 'published_at' => now()]);
        $this->finishBulk(__('admin.products.bulk_published'));
    }

    public function bulkArchive(): void
    {
        $this->authorize('products.publish');
        Product::whereIn('id', $this->selected)->update(['status' => ProductStatus::Archived->value]);
        $this->finishBulk(__('admin.products.bulk_archived'));
    }

    public function bulkBadge(string $flag): void
    {
        $this->authorize('products.update');
        $flags = ['is_new', 'is_best_seller', 'is_smart_pick'];
        if (! in_array($flag, [...$flags, 'none'], true)) {
            return;
        }

        $update = array_fill_keys($flags, false);
        if ($flag !== 'none') {
            $update[$flag] = true;
        }
        Product::whereIn('id', $this->selected)->update($update);
        $this->finishBulk(__('admin.products.bulk_badged'));
    }

    public function bulkDelete(): void
    {
        $this->authorize('products.delete');
        Product::whereIn('id', $this->selected)->delete();
        $this->finishBulk(__('admin.products.bulk_deleted'));
    }

    private function finishBulk(string $title): void
    {
        $count = count($this->selected);
        $this->selected = [];
        $this->dispatch('toast', tone: 'success', title: $title, message: trans_choice('admin.products.n_updated', $count, ['count' => $count]));
    }

    // --- Row actions -------------------------------------------------------

    public function duplicate(int $id): void
    {
        $this->authorize('products.create');
        $product = Product::with(['variants', 'specs'])->findOrFail($id);

        // slug_ar/slug_en are generated STORED columns — never copy them.
        $copy = $product->replicate(['search_text', 'slug_ar', 'slug_en']);
        $copy->sku = $this->uniqueSku($product->sku.'-COPY');
        $copy->status = ProductStatus::Draft->value;
        $copy->published_at = null;
        $copy->setTranslations('slug', collect($product->getTranslations('slug'))
            ->map(fn ($v) => $v.'-copy')->all());
        $copy->save();

        foreach ($product->variants as $variant) {
            $newVariant = $variant->replicate();
            $newVariant->sku = $this->uniqueSku($variant->sku.'-COPY');
            $copy->variants()->save($newVariant);
        }
        foreach ($product->specs as $spec) {
            $copy->specs()->create($spec->only(['name', 'value', 'sort_order']));
        }

        $this->dispatch('toast', tone: 'success', title: __('admin.products.duplicated'), message: $copy->sku);
    }

    public function delete(int $id): void
    {
        $this->authorize('products.delete');
        Product::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.products.deleted'));
    }

    private function uniqueSku(string $base): string
    {
        $sku = $base;
        $n = 2;
        while (Product::where('sku', $sku)->exists()) {
            $sku = $base.'-'.$n++;
        }

        return $sku;
    }

    /** @return array<string, int> */
    public function tabCounts(): array
    {
        $counts = Product::query()->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');

        return [
            'all' => (int) $counts->sum(),
            'active' => (int) ($counts[ProductStatus::Active->value] ?? 0),
            'draft' => (int) ($counts[ProductStatus::Draft->value] ?? 0),
            'archived' => (int) ($counts[ProductStatus::Archived->value] ?? 0),
        ];
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        $query = Product::query()
            ->with(['brand', 'category'])
            ->withSum('variants as stock_total', 'stock_qty')
            ->when($this->tab !== 'all', fn (Builder $q) => $q->where('status', $this->tab))
            ->when($this->search !== '', function (Builder $q) use ($locale) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w->where('sku', 'like', $like)
                    ->orWhere('name->'.$locale, 'like', $like)
                    ->orWhere('search_text', 'like', $like)
                    ->orWhereHas('brand', fn ($b) => $b->where('name', 'like', $like)));
            })
            ->when($this->category !== '', fn (Builder $q) => $q->where('category_id', $this->category))
            ->when($this->brand !== '', fn (Builder $q) => $q->where('brand_id', $this->brand))
            ->when($this->stock !== '', fn (Builder $q) => $q->where('stock_status', $this->stock));

        $query->orderBy(match ($this->sortCol) {
            'price' => 'min_price',
            'stock' => 'stock_status',
            default => 'sku',
        }, $this->sortCol ? $this->sortDir : 'asc');

        return view('livewire.admin.products.index', [
            'rows' => $query->paginate(12),
            'counts' => $this->tabCounts(),
            'categories' => Category::query()->orderBy('sort_order')->get()
                ->map(fn (Category $c) => ['value' => $c->id, 'label' => $c->getTranslation('name', $locale, false)])->all(),
            'brands' => Brand::query()->orderBy('name')->get()
                ->map(fn (Brand $b) => ['value' => $b->id, 'label' => $b->name])->all(),
        ]);
    }
}

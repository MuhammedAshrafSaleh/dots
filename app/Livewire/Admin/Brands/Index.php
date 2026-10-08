<?php

namespace App\Livewire\Admin\Brands;

use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Brand;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    #[Url(except: '', history: false)]
    public string $origin = '';

    #[Url(except: '', history: false)]
    public string $status = '';

    public function updatingOrigin(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function feature(int $id): void
    {
        $this->authorize('brands.update');
        // Setting featured true triggers the model hook that clears the others.
        Brand::findOrFail($id)->update(['is_featured' => true]);
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('brands.update');
        $brand = Brand::findOrFail($id);
        $brand->update(['is_active' => ! $brand->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('brands.delete');
        Brand::findOrFail($id)->delete();

        $this->dispatch('toast', tone: 'success', title: __('admin.brands.deleted'));
    }

    /** @return array<int, array{value: string, label: string}> */
    public function origins(): array
    {
        $locale = app()->getLocale();

        return Brand::query()->get()
            ->map(fn (Brand $b) => $b->getTranslation('origin_country', $locale, false))
            ->filter()->unique()->sort()->values()
            ->map(fn ($o) => ['value' => $o, 'label' => $o])->all();
    }

    public function render(): View
    {
        $locale = app()->getLocale();

        $query = Brand::query()
            ->withCount('products')
            ->when($this->search !== '', function (Builder $q) use ($locale) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w->where('name', 'like', $like)
                    ->orWhere('origin_country->'.$locale, 'like', $like));
            })
            ->when($this->origin !== '', fn (Builder $q) => $q->where('origin_country->'.$locale, $this->origin))
            ->when($this->status !== '', fn (Builder $q) => $q->where('is_active', $this->status === 'on'));

        $query->orderBy(match ($this->sortCol) {
            'products' => 'products_count',
            'name' => 'name',
            default => 'sort_order',
        }, $this->sortCol ? $this->sortDir : 'asc');

        return view('livewire.admin.brands.index', [
            'rows' => $query->paginate(12),
            'origins' => $this->origins(),
        ]);
    }
}

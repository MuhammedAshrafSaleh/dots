<?php

namespace App\Livewire\Admin\Widgets;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Modelable;
use Livewire\Component;

/**
 * Searchable product picker with a draggable chosen list (AProductPicker).
 * Bind from a parent form with `wire:model` on the selected id array:
 *   <livewire:admin.widgets.product-picker wire:model="relatedIds" :exclude="$id" />
 */
class ProductPicker extends Component
{
    /** @var array<int, int> */
    #[Modelable]
    public array $selected = [];

    /** Product id to keep out of the options (e.g. the product being edited). */
    public ?int $exclude = null;

    public ?string $label = null;

    public string $search = '';

    public function add(int $id): void
    {
        if (! in_array($id, $this->selected, true)) {
            $this->selected[] = $id;
        }
        $this->search = '';
    }

    public function remove(int $id): void
    {
        $this->selected = array_values(array_filter($this->selected, fn ($x) => $x !== $id));
    }

    /**
     * @param  array<int, int|string>  $ids
     */
    public function reorder(array $ids): void
    {
        $this->selected = array_values(array_map('intval', $ids));
    }

    /** @return Collection<int, Product> */
    public function chosen(): Collection
    {
        if ($this->selected === []) {
            return collect();
        }

        $byId = Product::query()->whereIn('id', $this->selected)->get()->keyBy('id');

        return collect($this->selected)
            ->map(fn ($id) => $byId->get($id))
            ->filter()
            ->values();
    }

    /** @return array<int, array{value: int, label: string}> */
    public function options(): array
    {
        $term = trim($this->search);

        return Product::query()
            ->whereNotIn('id', array_merge($this->selected, array_filter([$this->exclude])))
            ->when($term !== '', function ($q) use ($term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
                $q->where(fn ($w) => $w->where('sku', 'like', $like)->orWhere('search_text', 'like', $like));
            })
            ->limit(20)->get()
            ->map(fn (Product $p) => [
                'value' => $p->id,
                'label' => $p->sku.' — '.($p->getTranslation('name', app()->getLocale(), false) ?: $p->sku),
            ])->all();
    }

    public function render(): View
    {
        return view('livewire.admin.widgets.product-picker', [
            'chosen' => $this->chosen(),
            'options' => $this->options(),
        ]);
    }
}

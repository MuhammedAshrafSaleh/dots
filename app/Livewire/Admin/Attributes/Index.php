<?php

namespace App\Livewire\Admin\Attributes;

use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Attribute;
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
    public string $displayType = '';

    #[Url(except: '', history: false)]
    public string $usage = '';

    public function updatingDisplayType(): void
    {
        $this->resetPage();
    }

    public function updatingUsage(): void
    {
        $this->resetPage();
    }

    public function toggleFilterable(int $id): void
    {
        $this->authorize('attributes.update');
        $attribute = Attribute::findOrFail($id);
        $attribute->update(['is_filterable' => ! $attribute->is_filterable]);
    }

    public function toggleVariants(int $id): void
    {
        $this->authorize('attributes.update');
        $attribute = Attribute::findOrFail($id);
        $attribute->update(['is_variant_axis' => ! $attribute->is_variant_axis]);
    }

    public function duplicate(int $id): void
    {
        $this->authorize('attributes.create');
        $attribute = Attribute::with('values')->findOrFail($id);

        $copy = $attribute->replicate();
        $copy->code = $this->uniqueCode($attribute->code.'-copy');
        $copy->save();

        foreach ($attribute->values as $value) {
            $copy->values()->create($value->only(['value', 'label', 'swatch', 'sort_order']));
        }

        $this->dispatch('toast', tone: 'success', title: __('admin.attributes.duplicated'));
    }

    public function delete(int $id): void
    {
        $this->authorize('attributes.delete');
        Attribute::findOrFail($id)->delete();

        $this->dispatch('toast', tone: 'success', title: __('admin.attributes.deleted'));
    }

    private function uniqueCode(string $base): string
    {
        $code = $base;
        $n = 2;
        while (Attribute::where('code', $code)->exists()) {
            $code = $base.'-'.$n++;
        }

        return $code;
    }

    public function render(): View
    {
        $query = Attribute::query()
            ->withCount('values')
            ->with(['values' => fn ($q) => $q->limit(4)])
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w->where('code', 'like', $like)
                    ->orWhere('name->en', 'like', $like)
                    ->orWhere('name->ar', 'like', $like));
            })
            ->when($this->displayType !== '', fn (Builder $q) => $q->where('display', $this->displayType))
            ->when($this->usage !== '', fn (Builder $q) => $q->where('is_variant_axis', $this->usage === 'y'));

        $query->orderBy(match ($this->sortCol) {
            'display' => 'display',
            default => 'sort_order',
        }, $this->sortCol ? $this->sortDir : 'asc');

        return view('livewire.admin.attributes.index', [
            'rows' => $query->paginate(12),
        ]);
    }
}

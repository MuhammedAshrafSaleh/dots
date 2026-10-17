<?php

namespace App\Livewire\Admin\Delivery;

use App\Models\DeliveryMethod;
use App\Models\Governorate;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    public bool $showDrawer = false;

    public ?int $editingId = null;

    public ?string $icon = 'truck';

    /** @var array<string, string> */
    public array $name = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $note = ['ar' => '', 'en' => ''];

    public string $price = '0';

    public string $free_over_amount = '';

    public int $sort_order = 0;

    public bool $price_is_from = false;

    public bool $requires_address = true;

    public bool $is_active = true;

    /** @var array<int, int> */
    public array $governorates = [];

    public function openCreate(): void
    {
        $this->authorize('delivery_methods.create');
        $this->resetForm();
        $this->sort_order = (int) DeliveryMethod::max('sort_order') + 1;
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('delivery_methods.update');
        $method = DeliveryMethod::with('governorates')->findOrFail($id);

        $this->editingId = $method->id;
        $this->icon = $method->icon ?: 'truck';
        foreach (['name', 'description', 'note'] as $f) {
            $this->{$f} = ['ar' => $method->getTranslation($f, 'ar', false), 'en' => $method->getTranslation($f, 'en', false)];
        }
        $this->price = (string) (float) $method->price;
        $this->free_over_amount = $method->free_over_amount !== null ? (string) (float) $method->free_over_amount : '';
        $this->sort_order = (int) $method->sort_order;
        $this->price_is_from = (bool) $method->price_is_from;
        $this->requires_address = (bool) $method->requires_address;
        $this->is_active = (bool) $method->is_active;
        $this->governorates = $method->governorates->pluck('id')->map(fn ($i) => (int) $i)->all();

        $this->resetErrorBag();
        $this->showDrawer = true;
    }

    public function toggleGov(int $id): void
    {
        $this->governorates = in_array($id, $this->governorates, true)
            ? array_values(array_filter($this->governorates, fn ($x) => $x !== $id))
            : [...$this->governorates, $id];
    }

    public function selectAllGovs(): void
    {
        $this->governorates = Governorate::query()->pluck('id')->map(fn ($i) => (int) $i)->all();
    }

    public function clearGovs(): void
    {
        $this->governorates = [];
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'delivery_methods.update' : 'delivery_methods.create');

        $data = $this->validate([
            'name.en' => ['required', 'string', 'max:255'],
            'name.ar' => ['required', 'string', 'max:255'],
            'description.en' => ['nullable', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string', 'max:255'],
            'note.en' => ['nullable', 'string', 'max:255'],
            'note.ar' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'free_over_amount' => ['nullable', 'numeric', 'min:0'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $method = $this->editingId ? DeliveryMethod::findOrFail($this->editingId) : new DeliveryMethod;
        $method->fill([
            'icon' => $this->icon ?: null,
            'name' => $this->name,
            'description' => $this->description,
            'note' => $this->note,
            'price' => $data['price'],
            'free_over_amount' => $data['free_over_amount'] !== '' ? $data['free_over_amount'] : null,
            'sort_order' => $data['sort_order'],
            'price_is_from' => $this->price_is_from,
            'requires_address' => $this->requires_address,
            'is_active' => $this->is_active,
        ]);
        $method->save();
        $method->governorates()->sync($this->governorates);

        $this->showDrawer = false;
        $this->dispatch('toast', tone: 'success', title: __('admin.delivery.saved'), message: $method->getTranslation('name', app()->getLocale(), false));
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('delivery_methods.update');
        $method = DeliveryMethod::findOrFail($id);
        $method->update(['is_active' => ! $method->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('delivery_methods.delete');
        DeliveryMethod::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.delivery.deleted'));
    }

    /**
     * Drag reorder. $ids is the new order of method ids.
     *
     * @param  array<int, int|string>  $ids
     */
    public function reorder(array $ids): void
    {
        $this->authorize('delivery_methods.update');
        foreach (array_values($ids) as $position => $id) {
            DeliveryMethod::whereKey((int) $id)->update(['sort_order' => $position]);
        }
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->icon = 'truck';
        $this->name = ['ar' => '', 'en' => ''];
        $this->description = ['ar' => '', 'en' => ''];
        $this->note = ['ar' => '', 'en' => ''];
        $this->price = '0';
        $this->free_over_amount = '';
        $this->sort_order = 0;
        $this->price_is_from = false;
        $this->requires_address = true;
        $this->is_active = true;
        $this->governorates = [];
        $this->resetErrorBag();
    }

    public function render(): View
    {
        return view('livewire.admin.delivery.index', [
            'rows' => DeliveryMethod::query()->withCount('governorates')->orderBy('sort_order')->get(),
            'govs' => Governorate::query()->orderBy('sort_order')->get(),
            'govCount' => Governorate::query()->count(),
        ]);
    }
}

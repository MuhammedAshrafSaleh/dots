<?php

namespace App\Livewire\Admin\Coupons;

use App\Enums\CouponType;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Coupon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    public bool $showDrawer = false;

    public ?int $editingId = null;

    // Form state.
    public string $code = '';

    public string $type = 'percent';

    public string $value = '';

    public string $min_order_total = '0';

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public ?string $usage_limit = null;

    public int $used_count = 0;

    public bool $once_per_customer = false;

    public bool $is_active = true;

    public function openCreate(): void
    {
        $this->authorize('coupons.create');
        $this->resetForm();
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $this->authorize('coupons.update');
        $coupon = Coupon::findOrFail($id);

        $this->editingId = $coupon->id;
        $this->code = $coupon->code;
        $this->type = $coupon->type->value;
        $this->value = (string) (float) $coupon->value;
        $this->min_order_total = (string) (float) $coupon->min_order_total;
        $this->starts_at = $coupon->starts_at?->format('Y-m-d');
        $this->ends_at = $coupon->ends_at?->format('Y-m-d');
        $this->usage_limit = $coupon->usage_limit !== null ? (string) $coupon->usage_limit : null;
        $this->used_count = (int) $coupon->used_count;
        $this->once_per_customer = (bool) $coupon->once_per_customer;
        $this->is_active = (bool) $coupon->is_active;

        $this->resetErrorBag();
        $this->showDrawer = true;
    }

    public function generate(): void
    {
        $this->code = Str::upper(Str::random(8));
    }

    public function save(): void
    {
        $this->authorize($this->editingId ? 'coupons.update' : 'coupons.create');

        $this->code = Str::upper(trim($this->code));

        $data = $this->validate([
            'code' => ['required', 'string', 'max:64', 'alpha_dash', Rule::unique('coupons', 'code')->ignore($this->editingId)],
            'type' => ['required', Rule::enum(CouponType::class)],
            'value' => ['required', 'numeric', 'min:0'],
            'min_order_total' => ['required', 'numeric', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        if ($this->type === CouponType::Percent->value && (float) $this->value > 100) {
            $this->addError('value', __('validation.max.numeric', ['attribute' => __('admin.coupons.value'), 'max' => 100]));

            return;
        }

        $coupon = $this->editingId ? Coupon::findOrFail($this->editingId) : new Coupon;
        $coupon->fill([
            'code' => $data['code'],
            'type' => $data['type'],
            'value' => $data['value'],
            'min_order_total' => $data['min_order_total'],
            'starts_at' => $data['starts_at'] ?: null,
            'ends_at' => $data['ends_at'] ?: null,
            'usage_limit' => $data['usage_limit'] !== null && $data['usage_limit'] !== '' ? (int) $data['usage_limit'] : null,
            'once_per_customer' => $this->once_per_customer,
            'is_active' => $this->is_active,
        ]);
        $coupon->save();

        $this->showDrawer = false;
        $this->dispatch('toast', tone: 'success', title: __('admin.coupons.saved'), message: $coupon->code);
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('coupons.update');
        $coupon = Coupon::findOrFail($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('coupons.delete');
        Coupon::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.coupons.deleted'));
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->code = '';
        $this->type = 'percent';
        $this->value = '';
        $this->min_order_total = '0';
        $this->starts_at = null;
        $this->ends_at = null;
        $this->usage_limit = null;
        $this->used_count = 0;
        $this->once_per_customer = false;
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function render(): View
    {
        $query = Coupon::query()
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where('code', 'like', $like);
            })
            ->orderByDesc('id');

        return view('livewire.admin.coupons.index', [
            'rows' => $query->paginate(12),
        ]);
    }
}

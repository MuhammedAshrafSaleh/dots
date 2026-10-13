<?php

namespace App\Livewire\Admin\Services;

use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    public function toggleActive(int $id): void
    {
        $this->authorize('services.update');
        $service = Service::findOrFail($id);
        $service->update(['is_active' => ! $service->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('services.delete');
        Service::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.services.deleted'));
    }

    public function render(): View
    {
        $query = Service::query()
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w
                    ->where('title->ar', 'like', $like)
                    ->orWhere('title->en', 'like', $like));
            })
            ->orderBy('sort_order');

        return view('livewire.admin.services.index', [
            'rows' => $query->paginate(20),
        ]);
    }
}

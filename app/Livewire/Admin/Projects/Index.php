<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Project;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    public string $statusTab = 'current';

    public function updatingStatusTab(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('projects.update');
        $project = Project::findOrFail($id);
        $project->update(['is_active' => ! $project->is_active]);
    }

    public function delete(int $id): void
    {
        $this->authorize('projects.delete');
        Project::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.projects.deleted'));
    }

    public function render(): View
    {
        $query = Project::query()
            ->where('status', $this->statusTab)
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w
                    ->where('title->ar', 'like', $like)
                    ->orWhere('title->en', 'like', $like));
            })
            ->orderBy('sort_order');

        return view('livewire.admin.projects.index', [
            'rows' => $query->paginate(20),
        ]);
    }
}

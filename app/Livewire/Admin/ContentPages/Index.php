<?php

namespace App\Livewire\Admin\ContentPages;

use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Page;
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
        $this->authorize('content_pages.update');
        $page = Page::findOrFail($id);
        $page->update(['is_active' => ! $page->is_active]);
    }

    public function toggleFooter(int $id): void
    {
        $this->authorize('content_pages.update');
        $page = Page::findOrFail($id);
        $page->update(['show_in_footer' => ! $page->show_in_footer]);
    }

    public function delete(int $id): void
    {
        $this->authorize('content_pages.delete');
        Page::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.content_pages.deleted'));
    }

    public function render(): View
    {
        $query = Page::query()
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w
                    ->where('title->ar', 'like', $like)
                    ->orWhere('title->en', 'like', $like)
                    ->orWhere('key', 'like', $like));
            })
            ->orderBy('sort_order');

        return view('livewire.admin.content-pages.index', [
            'rows' => $query->paginate(20),
        ]);
    }
}

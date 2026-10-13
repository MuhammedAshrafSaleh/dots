<?php

namespace App\Livewire\Admin\Blog;

use App\Enums\PostStatus;
use App\Livewire\Admin\Concerns\WithAdminTable;
use App\Models\Post;
use App\Models\PostCategory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use WithAdminTable;

    public string $statusTab = 'all';

    public string $categoryFilter = '';

    public string $featuredFilter = '';

    public function updatingStatusTab(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFeaturedFilter(): void
    {
        $this->resetPage();
    }

    public function toggleFeatured(int $id): void
    {
        $this->authorize('posts.update');
        $post = Post::findOrFail($id);
        $post->update(['is_featured' => ! $post->is_featured]);
    }

    public function bulkPublish(): void
    {
        $this->authorize('posts.update');
        Post::whereIn('id', $this->selected)->update([
            'status' => PostStatus::Published->value,
            'published_at' => now(),
        ]);
        $this->selected = [];
        $this->dispatch('toast', tone: 'success', title: __('admin.blog.bulk_published'));
    }

    public function bulkDraft(): void
    {
        $this->authorize('posts.update');
        Post::whereIn('id', $this->selected)->update(['status' => PostStatus::Draft->value]);
        $this->selected = [];
        $this->dispatch('toast', tone: 'success', title: __('admin.blog.bulk_drafted'));
    }

    public function delete(int $id): void
    {
        $this->authorize('posts.delete');
        Post::findOrFail($id)->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.blog.deleted'));
    }

    public function render(): View
    {
        $query = Post::query()
            ->with('category')
            ->when($this->statusTab !== 'all', fn (Builder $q) => $q->where('status', $this->statusTab))
            ->when($this->categoryFilter !== '', fn (Builder $q) => $q->where('post_category_id', (int) $this->categoryFilter))
            ->when($this->featuredFilter === 'y', fn (Builder $q) => $q->where('is_featured', true))
            ->when($this->featuredFilter === 'n', fn (Builder $q) => $q->where('is_featured', false))
            ->when($this->search !== '', function (Builder $q) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->search)).'%';
                $q->where(fn (Builder $w) => $w
                    ->where('title->ar', 'like', $like)
                    ->orWhere('title->en', 'like', $like));
            })
            ->orderByDesc('published_at')
            ->orderByDesc('created_at');

        return view('livewire.admin.blog.index', [
            'rows' => $query->paginate(20),
            'categories' => PostCategory::orderBy('sort_order')->get(),
        ]);
    }
}

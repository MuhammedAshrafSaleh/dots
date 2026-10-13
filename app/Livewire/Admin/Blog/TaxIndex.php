<?php

namespace App\Livewire\Admin\Blog;

use App\Models\PostCategory;
use App\Models\Tag;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class TaxIndex extends Component
{
    public string $activeTab = 'cat';

    /**
     * Each row: ['id' => int|null, 'name_en' => '', 'name_ar' => '', 'slug_en' => '', 'slug_ar' => '', 'sort_order' => int, 'count' => int]
     *
     * @var array<int, array<string, mixed>>
     */
    public array $cats = [];

    /**
     * Each row: ['id' => int|null, 'name_en' => '', 'name_ar' => '', 'slug' => '']
     *
     * @var array<int, array<string, mixed>>
     */
    public array $tags = [];

    public function mount(): void
    {
        $this->loadCats();
        $this->loadTags();
    }

    private function loadCats(): void
    {
        $this->cats = PostCategory::withCount('posts')
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($c) => [
                'id' => $c->id,
                'name_en' => $c->getTranslation('name', 'en', false),
                'name_ar' => $c->getTranslation('name', 'ar', false),
                'slug_en' => $c->getTranslation('slug', 'en', false),
                'slug_ar' => $c->getTranslation('slug', 'ar', false),
                'sort_order' => $c->sort_order,
                'count' => $c->posts_count,
            ])
            ->values()
            ->all();
    }

    private function loadTags(): void
    {
        $this->tags = Tag::withCount('posts')
            ->orderBy('id')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name_en' => $t->getTranslation('name', 'en', false),
                'name_ar' => $t->getTranslation('name', 'ar', false),
                'slug' => $t->slug,
                'count' => $t->posts_count,
            ])
            ->values()
            ->all();
    }

    public function addCategory(): void
    {
        $this->authorize('post_categories.manage');
        array_unshift($this->cats, [
            'id' => null,
            'name_en' => '',
            'name_ar' => '',
            'slug_en' => '',
            'slug_ar' => '',
            'sort_order' => count($this->cats) + 1,
            'count' => 0,
        ]);
    }

    public function saveCategory(int $index): void
    {
        $this->authorize('post_categories.manage');

        $row = $this->cats[$index];
        $this->validateOnly("cats.$index.name_en", ["cats.$index.name_en" => ['required', 'string', 'max:255']]);
        $this->validateOnly("cats.$index.name_ar", ["cats.$index.name_ar" => ['required', 'string', 'max:255']]);
        $this->validateOnly("cats.$index.slug_en", ["cats.$index.slug_en" => ['required', 'string', 'max:255']]);
        $this->validateOnly("cats.$index.slug_ar", ["cats.$index.slug_ar" => ['required', 'string', 'max:255']]);

        $data = [
            'name' => ['en' => $row['name_en'], 'ar' => $row['name_ar']],
            'slug' => ['en' => $row['slug_en'], 'ar' => $row['slug_ar']],
            'sort_order' => (int) $row['sort_order'],
        ];

        if ($row['id']) {
            PostCategory::findOrFail((int) $row['id'])->update($data);
        } else {
            $cat = PostCategory::create($data);
            $this->cats[$index]['id'] = $cat->id;
        }

        $this->dispatch('toast', tone: 'success', title: __('admin.blog.tax_saved'));
    }

    public function deleteCategory(int $index): void
    {
        $this->authorize('post_categories.manage');
        $row = $this->cats[$index];
        if ($row['id']) {
            PostCategory::findOrFail((int) $row['id'])->delete();
        }
        array_splice($this->cats, $index, 1);
        $this->dispatch('toast', tone: 'success', title: __('admin.blog.tax_deleted'));
    }

    public function addTag(): void
    {
        $this->authorize('post_categories.manage');
        array_unshift($this->tags, [
            'id' => null,
            'name_en' => '',
            'name_ar' => '',
            'slug' => '',
            'count' => 0,
        ]);
    }

    public function saveTag(int $index): void
    {
        $this->authorize('post_categories.manage');

        $row = $this->tags[$index];
        $this->validateOnly("tags.$index.name_en", ["tags.$index.name_en" => ['required', 'string', 'max:255']]);
        $this->validateOnly("tags.$index.name_ar", ["tags.$index.name_ar" => ['required', 'string', 'max:255']]);
        $this->validateOnly("tags.$index.slug", ["tags.$index.slug" => ['required', 'string', 'max:255']]);

        $data = [
            'name' => ['en' => $row['name_en'], 'ar' => $row['name_ar']],
            'slug' => $row['slug'],
        ];

        if ($row['id']) {
            Tag::findOrFail((int) $row['id'])->update($data);
        } else {
            $tag = Tag::create($data);
            $this->tags[$index]['id'] = $tag->id;
        }

        $this->dispatch('toast', tone: 'success', title: __('admin.blog.tax_saved'));
    }

    public function deleteTag(int $index): void
    {
        $this->authorize('post_categories.manage');
        $row = $this->tags[$index];
        if ($row['id']) {
            Tag::findOrFail((int) $row['id'])->delete();
        }
        array_splice($this->tags, $index, 1);
        $this->dispatch('toast', tone: 'success', title: __('admin.blog.tax_deleted'));
    }

    public function render(): View
    {
        return view('livewire.admin.blog.tax-index');
    }
}

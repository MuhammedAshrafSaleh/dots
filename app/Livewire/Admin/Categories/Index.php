<?php

namespace App\Livewire\Admin\Categories;

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Models\Category;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Index extends Component
{
    use InteractsWithAdminForms;
    use WithFileUploads;

    /** @var array<int, int> */
    public array $expanded = [];

    public ?int $selectedId = null;

    // Edit-panel form state (loaded on select).
    public int $level = 1;

    public ?int $parent_id = null;

    /** @var array<string, string> */
    public array $name = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $slug = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $buying_guide_title = ['ar' => '', 'en' => ''];

    public ?string $icon = null;

    public bool $show_on_home = false;

    public bool $show_in_footer = false;

    public bool $is_active = true;

    /** @var array<string, string> */
    public array $meta_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['ar' => '', 'en' => ''];

    /** @var array<string, array{id: int|null, question: array<string,string>, answer: array<string,string>}> */
    public array $faqs = [];

    /** @var array<int, int> */
    public array $featuredIds = [];

    public mixed $imageUpload = null;

    public mixed $heroUpload = null;

    /** @var array<string, bool> */
    public array $remove = ['image' => false, 'hero' => false];

    public function mount(): void
    {
        $first = Category::query()->where('level', 1)->orderBy('sort_order')->first();
        if ($first) {
            $this->expanded = [$first->id];
            $this->select($first->id);
        }
    }

    public function toggle(int $id): void
    {
        $this->expanded = in_array($id, $this->expanded, true)
            ? array_values(array_filter($this->expanded, fn ($x) => $x !== $id))
            : [...$this->expanded, $id];
    }

    public function expandAll(): void
    {
        $this->expanded = Category::query()->where('level', '<', 3)->pluck('id')->all();
    }

    public function select(int $id): void
    {
        $category = Category::with(['faqs', 'featuredProducts'])->findOrFail($id);

        $this->selectedId = $category->id;
        $this->level = (int) $category->level;
        $this->parent_id = $category->parent_id;
        $this->icon = $category->icon;
        foreach (['name', 'slug', 'description', 'buying_guide_title', 'meta_title', 'meta_description'] as $f) {
            $this->{$f} = ['ar' => $category->getTranslation($f, 'ar', false), 'en' => $category->getTranslation($f, 'en', false)];
        }
        $this->show_on_home = (bool) $category->show_on_home;
        $this->show_in_footer = (bool) $category->show_in_footer;
        $this->is_active = (bool) $category->is_active;
        $this->featuredIds = $category->featuredProducts->pluck('id')->map(fn ($i) => (int) $i)->all();

        $this->faqs = [];
        foreach ($category->faqs as $faq) {
            $this->faqs[(string) Str::uuid()] = [
                'id' => $faq->id,
                'question' => ['ar' => $faq->getTranslation('question', 'ar', false), 'en' => $faq->getTranslation('question', 'en', false)],
                'answer' => ['ar' => $faq->getTranslation('answer', 'ar', false), 'en' => $faq->getTranslation('answer', 'en', false)],
            ];
        }

        $this->remove = ['image' => false, 'hero' => false];
        $this->imageUpload = null;
        $this->heroUpload = null;
        $this->resetValidation();
    }

    public function addMain(): void
    {
        $this->authorize('categories.create');
        $category = Category::create([
            'level' => 1,
            'name' => ['ar' => 'قسم جديد', 'en' => 'New category'],
            'slug' => Slug::fromTranslations(['ar' => 'قسم-'.Str::random(4), 'en' => 'category-'.Str::lower(Str::random(4))]),
            'sort_order' => (int) Category::where('level', 1)->max('sort_order') + 1,
            'is_active' => true,
        ]);
        $this->select($category->id);
    }

    public function addChild(int $parentId): void
    {
        $this->authorize('categories.create');
        $parent = Category::findOrFail($parentId);
        $level = min(3, (int) $parent->level + 1);

        $category = Category::create([
            'level' => $level,
            'parent_id' => $parent->id,
            'name' => ['ar' => 'قسم فرعي', 'en' => 'New sub-category'],
            'slug' => Slug::fromTranslations(['ar' => 'فرعي-'.Str::random(4), 'en' => 'sub-'.Str::lower(Str::random(4))]),
            'sort_order' => (int) Category::where('parent_id', $parent->id)->max('sort_order') + 1,
            'is_active' => true,
        ]);
        $this->expanded = array_values(array_unique([...$this->expanded, $parent->id]));
        $this->select($category->id);
    }

    public function generateSlug(): void
    {
        $this->fillSlugsFrom('name', 'slug');
    }

    public function addFaq(): void
    {
        $this->faqs[(string) Str::uuid()] = [
            'id' => null,
            'question' => ['ar' => '', 'en' => ''],
            'answer' => ['ar' => '', 'en' => ''],
        ];
    }

    public function removeFaq(string $key): void
    {
        unset($this->faqs[$key]);
    }

    /** @param  array<int, string>  $keys */
    public function reorderFaqs(array $keys): void
    {
        $ordered = [];
        foreach ($keys as $key) {
            if (isset($this->faqs[$key])) {
                $ordered[$key] = $this->faqs[$key];
            }
        }
        $this->faqs = $ordered;
    }

    public function removeMedia(string $collection): void
    {
        if (array_key_exists($collection, $this->remove)) {
            $this->remove[$collection] = true;
        }
    }

    /**
     * Drag reorder within one level. $ids is the new order of sibling ids.
     *
     * @param  array<int, int|string>  $ids
     */
    public function reorder(array $ids): void
    {
        $this->authorize('categories.update');
        foreach (array_values($ids) as $position => $id) {
            Category::whereKey((int) $id)->update(['sort_order' => $position]);
        }
    }

    public function save(): void
    {
        $this->authorize('categories.update');
        $category = Category::findOrFail($this->selectedId);

        $data = $this->validate([
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'slug.ar' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug_ar')->ignore($category->id)],
            'slug.en' => ['required', 'string', 'max:255', Rule::unique('categories', 'slug_en')->ignore($category->id)],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'buying_guide_title.ar' => ['nullable', 'string', 'max:255'],
            'buying_guide_title.en' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:64'],
            'meta_title.ar' => ['nullable', 'string', 'max:255'],
            'meta_title.en' => ['nullable', 'string', 'max:255'],
            'meta_description.ar' => ['nullable', 'string', 'max:500'],
            'meta_description.en' => ['nullable', 'string', 'max:500'],
            'faqs.*.question.ar' => ['required', 'string'],
            'faqs.*.question.en' => ['required', 'string'],
            'faqs.*.answer.ar' => ['required', 'string'],
            'faqs.*.answer.en' => ['required', 'string'],
            'imageUpload' => ['nullable', 'image', 'max:5120'],
            'heroUpload' => ['nullable', 'image', 'max:5120'],
        ]);

        DB::transaction(function () use ($category, $data) {
            $category->fill([
                'name' => $data['name'],
                'slug' => $data['slug'],
                'description' => $this->description,
                'buying_guide_title' => $this->buying_guide_title,
                'icon' => $this->level === 1 ? $this->icon : null,
                'show_on_home' => $this->show_on_home,
                'show_in_footer' => $this->show_in_footer,
                'is_active' => $this->is_active,
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
            ]);
            $category->save();

            // FAQs: upsert by id, delete removed, sort by order.
            $keptIds = [];
            $order = 0;
            foreach ($this->faqs as $row) {
                $faq = $row['id'] ? $category->faqs()->whereKey($row['id'])->first() ?? $category->faqs()->make() : $category->faqs()->make();
                $faq->fill(['question' => $row['question'], 'answer' => $row['answer'], 'sort_order' => $order++]);
                $category->faqs()->save($faq);
                $keptIds[] = $faq->id;
            }
            $category->faqs()->whereNotIn('id', $keptIds ?: [0])->delete();

            // Top picks.
            $sync = [];
            foreach (array_values($this->featuredIds) as $i => $pid) {
                $sync[(int) $pid] = ['sort_order' => $i];
            }
            $category->featuredProducts()->sync($sync);
        });

        $this->syncMedia($category, 'image', $this->imageUpload);
        $this->syncMedia($category, 'hero', $this->heroUpload);

        $this->imageUpload = null;
        $this->heroUpload = null;
        $this->remove = ['image' => false, 'hero' => false];

        $this->dispatch('toast', tone: 'success', title: __('admin.categories.saved'), message: $category->getTranslation('name', app()->getLocale(), false));
    }

    public function delete(int $id): void
    {
        $this->authorize('categories.delete');
        $category = Category::withCount(['products', 'children'])->findOrFail($id);

        if ($category->parent_id === null && ($category->products_count > 0 || $category->children_count > 0)) {
            $this->dispatch('toast', tone: 'error', title: __('admin.categories.cannot_delete_root'));

            return;
        }

        DB::transaction(function () use ($category) {
            // Move products and direct children up to the parent.
            $category->products()->update(['category_id' => $category->parent_id]);
            $category->children()->update([
                'parent_id' => $category->parent_id,
                'level' => max(1, (int) $category->level),
            ]);
            $category->delete();
        });

        if ($this->selectedId === $id) {
            $this->selectedId = null;
        }

        $this->dispatch('toast', tone: 'success', title: __('admin.categories.deleted'));
    }

    private function syncMedia(Category $category, string $collection, mixed $upload): void
    {
        if ($upload instanceof TemporaryUploadedFile) {
            $category->clearMediaCollection($collection);
            $category->addMedia($upload->getRealPath())
                ->usingFileName($upload->getClientOriginalName())
                ->toMediaCollection($collection);
        } elseif ($this->remove[$collection] ?? false) {
            $category->clearMediaCollection($collection);
        }
    }

    public function render(): View
    {
        $childQuery = fn ($q) => $q->withCount('products')->orderBy('sort_order');

        $tree = Category::query()
            ->where('level', 1)
            ->withCount('products')
            ->with(['children' => fn ($q) => $childQuery($q)->with(['children' => $childQuery])])
            ->orderBy('sort_order')
            ->get();

        return view('livewire.admin.categories.index', [
            'tree' => $tree,
            'selected' => $this->selectedId ? Category::with('parent')->find($this->selectedId) : null,
        ]);
    }
}

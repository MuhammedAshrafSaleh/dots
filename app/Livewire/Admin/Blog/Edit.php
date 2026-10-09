<?php

namespace App\Livewire\Admin\Blog;

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\Tag;
use App\Support\Html;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use InteractsWithAdminForms;
    use WithFileUploads;

    public ?Post $post = null;

    public string $activeTab = 'post';

    /** @var array<string, string> */
    public array $title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $slug = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $excerpt = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $body = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $author_name = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $author_role = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['ar' => '', 'en' => ''];

    public string $status = 'draft';

    public string $published_at = '';

    public bool $is_featured = false;

    public int|string $post_category_id = '';

    public string $read_minutes = '';

    /** @var array<int, int> */
    public array $selectedTagIds = [];

    /** @var array<int, int> */
    public array $selectedProductIds = [];

    public string $productSearch = '';

    public mixed $imageUpload = null;

    public mixed $authorPhotoUpload = null;

    public bool $removeImage = false;

    public bool $removeAuthorPhoto = false;

    public function mount(?Post $post = null): void
    {
        if ($post?->exists) {
            $this->post = $post;

            foreach (['title', 'slug', 'excerpt', 'body', 'author_name', 'author_role', 'meta_title', 'meta_description'] as $f) {
                $this->{$f} = [
                    'ar' => $post->getTranslation($f, 'ar', false),
                    'en' => $post->getTranslation($f, 'en', false),
                ];
            }

            $this->status = $post->status->value;
            $this->published_at = $post->published_at?->format('Y-m-d\TH:i') ?? '';
            $this->is_featured = (bool) $post->is_featured;
            $this->post_category_id = (string) $post->post_category_id;
            $this->read_minutes = $post->read_minutes ? (string) $post->read_minutes : '';
            $this->selectedTagIds = $post->tags()->pluck('tags.id')->map(fn ($id) => (int) $id)->all();
            $this->selectedProductIds = $post->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all();
        }
    }

    public function generateSlugs(): void
    {
        $this->fillSlugsFrom('title');
    }

    public function addProduct(int $id): void
    {
        if (! in_array($id, $this->selectedProductIds, true)) {
            $this->selectedProductIds[] = $id;
        }
        $this->productSearch = '';
    }

    public function removeProduct(int $id): void
    {
        $this->selectedProductIds = array_values(array_filter($this->selectedProductIds, fn ($v) => $v !== $id));
    }

    public function removeCoverImage(): void
    {
        $this->removeImage = true;
    }

    public function removeAuthorPhotoAction(): void
    {
        $this->removeAuthorPhoto = true;
    }

    public function save(): void
    {
        $this->authorize($this->post ? 'posts.update' : 'posts.create');

        try {
            $this->validate([
                'title.ar' => ['required', 'string', 'max:255'],
                'title.en' => ['required', 'string', 'max:255'],
                'slug.ar' => ['required', 'string', 'max:255'],
                'slug.en' => ['required', 'string', 'max:255'],
                'excerpt.ar' => ['nullable', 'string', 'max:500'],
                'excerpt.en' => ['nullable', 'string', 'max:500'],
                'body.ar' => ['nullable', 'string'],
                'body.en' => ['nullable', 'string'],
                'author_name.ar' => ['nullable', 'string', 'max:255'],
                'author_name.en' => ['nullable', 'string', 'max:255'],
                'author_role.ar' => ['nullable', 'string', 'max:255'],
                'author_role.en' => ['nullable', 'string', 'max:255'],
                'meta_title.ar' => ['nullable', 'string', 'max:255'],
                'meta_title.en' => ['nullable', 'string', 'max:255'],
                'meta_description.ar' => ['nullable', 'string', 'max:500'],
                'meta_description.en' => ['nullable', 'string', 'max:500'],
                'status' => ['required', 'in:draft,published,scheduled'],
                'published_at' => $this->status === 'scheduled' ? ['required', 'date'] : ['nullable', 'date'],
                'is_featured' => ['boolean'],
                'post_category_id' => ['required', 'integer', 'exists:post_categories,id'],
                'read_minutes' => ['nullable', 'integer', 'min:1', 'max:120'],
                'selectedTagIds.*' => ['integer'],
                'selectedProductIds.*' => ['integer'],
                'imageUpload' => ['nullable', 'image', 'max:5120'],
                'authorPhotoUpload' => ['nullable', 'image', 'max:5120'],
            ]);
        } catch (ValidationException $e) {
            $this->activeTab = $this->tabForField(array_key_first($e->errors()) ?? 'title.ar');
            $this->dispatch('toast', tone: 'error', title: __('admin.blog.has_errors'));
            throw $e;
        }

        $publishedAt = null;
        if ($this->status === 'scheduled' && $this->published_at !== '') {
            $publishedAt = $this->published_at;
        } elseif ($this->status === 'published') {
            $publishedAt = $this->published_at !== '' ? $this->published_at : now()->toDateTimeString();
        } elseif ($this->published_at !== '') {
            $publishedAt = $this->published_at;
        }

        $post = $this->post ?? new Post;
        $post->fill([
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'body' => Html::cleanTranslations($this->body),
            'author_name' => $this->author_name,
            'author_role' => $this->author_role,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'status' => $this->status,
            'published_at' => $publishedAt,
            'is_featured' => $this->is_featured,
            'post_category_id' => (int) $this->post_category_id,
            'read_minutes' => $this->read_minutes !== '' ? (int) $this->read_minutes : null,
        ]);
        $post->save();

        if ($this->imageUpload instanceof TemporaryUploadedFile) {
            $post->clearMediaCollection('cover');
            $post->addMedia($this->imageUpload->getRealPath())
                ->usingFileName($this->imageUpload->getClientOriginalName())
                ->toMediaCollection('cover');
        } elseif ($this->removeImage) {
            $post->clearMediaCollection('cover');
        }

        if ($this->authorPhotoUpload instanceof TemporaryUploadedFile) {
            $post->clearMediaCollection('author');
            $post->addMedia($this->authorPhotoUpload->getRealPath())
                ->usingFileName($this->authorPhotoUpload->getClientOriginalName())
                ->toMediaCollection('author');
        } elseif ($this->removeAuthorPhoto) {
            $post->clearMediaCollection('author');
        }

        $post->tags()->sync($this->selectedTagIds);

        $sync = [];
        foreach ($this->selectedProductIds as $order => $productId) {
            $sync[$productId] = ['sort_order' => $order];
        }
        $post->products()->sync($sync);

        $this->dispatch('toast', tone: 'success', title: $this->status === 'scheduled'
            ? __('admin.blog.scheduled')
            : __('admin.blog.saved'));
        $this->redirectRoute('admin.blog.edit', $post, navigate: true);
    }

    public function deletePost(): void
    {
        $this->authorize('posts.delete');
        $this->post?->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.blog.deleted'));
        $this->redirectRoute('admin.blog', navigate: true);
    }

    private function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'title'),
            str_starts_with($field, 'slug'),
            str_starts_with($field, 'excerpt'),
            str_starts_with($field, 'imageUpload') => 'post',
            str_starts_with($field, 'body') => 'body',
            str_starts_with($field, 'selectedProduct') => 'products',
            str_starts_with($field, 'meta_title'),
            str_starts_with($field, 'meta_description') => 'seo',
            default => 'post',
        };
    }

    /** @return array<int, string> */
    public function errorTabs(): array
    {
        $tabs = [];
        foreach ($this->getErrorBag()->keys() as $field) {
            $tabs[$this->tabForField($field)] = true;
        }

        return array_keys($tabs);
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Product> */
    public function productSearchResults()
    {
        if ($this->productSearch === '') {
            return collect();
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->productSearch)).'%';

        return Product::query()
            ->where(fn (Builder $q) => $q
                ->where('name->ar', 'like', $like)
                ->orWhere('name->en', 'like', $like))
            ->whereNotIn('id', $this->selectedProductIds ?: [0])
            ->limit(8)
            ->get();
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Product> */
    public function selectedProducts()
    {
        if (empty($this->selectedProductIds)) {
            return collect();
        }

        return Product::whereIn('id', $this->selectedProductIds)->get();
    }

    public function render(): View
    {
        $coverUrl = null;
        if ($this->imageUpload instanceof TemporaryUploadedFile) {
            $coverUrl = $this->imageUpload->temporaryUrl();
        } elseif (! $this->removeImage) {
            $url = $this->post?->getFirstMediaUrl('cover');
            $coverUrl = ($url !== '' && $url !== null) ? $url : null;
        }

        $authorPhotoUrl = null;
        if ($this->authorPhotoUpload instanceof TemporaryUploadedFile) {
            $authorPhotoUrl = $this->authorPhotoUpload->temporaryUrl();
        } elseif (! $this->removeAuthorPhoto) {
            $url = $this->post?->getFirstMediaUrl('author');
            $authorPhotoUrl = ($url !== '' && $url !== null) ? $url : null;
        }

        return view('livewire.admin.blog.edit', [
            'coverUrl' => $coverUrl,
            'authorPhotoUrl' => $authorPhotoUrl,
            'errorTabs' => $this->errorTabs(),
            'categories' => PostCategory::orderBy('sort_order')->get(),
            'allTags' => Tag::orderBy('id')->get(),
            'productResults' => $this->productSearchResults(),
            'pickedProducts' => $this->selectedProducts(),
        ]);
    }
}

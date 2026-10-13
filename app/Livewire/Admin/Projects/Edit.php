<?php

namespace App\Livewire\Admin\Projects;

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Support\Html;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use InteractsWithAdminForms;
    use WithFileUploads;

    public ?Project $project = null;

    public string $activeTab = 'basic';

    /** @var array<string, string> */
    public array $title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $slug = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $location = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $client = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $phase_label = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $scope_summary = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $about = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $challenge = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $solution = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $result = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['ar' => '', 'en' => ''];

    public string $status = 'current';

    public string $type = 'residential';

    public string $stage = 'design';

    public int $progress = 0;

    public string $start_date = '';

    public string $expected_completion = '';

    public string $completed_year = '';

    public bool $is_active = true;

    public bool $is_featured = false;

    public int $sort_order = 0;

    /** @var array<int, int> */
    public array $selectedProductIds = [];

    /** @var array<int, int> */
    public array $selectedServiceIds = [];

    public string $productSearch = '';

    public string $serviceSearch = '';

    /** @var array<int, mixed> */
    public array $galleryUploads = [];

    public mixed $imageUpload = null;

    public bool $removeImage = false;

    public function mount(?Project $project = null): void
    {
        if ($project?->exists) {
            $this->project = $project;

            foreach (['title', 'slug', 'location', 'client', 'phase_label', 'scope_summary',
                'about', 'challenge', 'solution', 'result', 'meta_title', 'meta_description'] as $f) {
                $this->{$f} = [
                    'ar' => $project->getTranslation($f, 'ar', false),
                    'en' => $project->getTranslation($f, 'en', false),
                ];
            }

            $this->status = $project->status->value;
            $this->type = $project->type->value;
            $this->stage = $project->stage->value;
            $this->progress = (int) $project->progress;
            $this->start_date = $project->start_date ? $project->start_date->format('Y-m-d') : '';
            $this->expected_completion = $project->expected_completion ? $project->expected_completion->format('Y-m-d') : '';
            $this->completed_year = $project->completed_year ? (string) $project->completed_year : '';
            $this->is_active = (bool) $project->is_active;
            $this->is_featured = (bool) $project->is_featured;
            $this->sort_order = (int) $project->sort_order;

            $this->selectedProductIds = $project->products()->pluck('products.id')->map(fn ($id) => (int) $id)->all();
            $this->selectedServiceIds = $project->services()->pluck('services.id')->map(fn ($id) => (int) $id)->all();
        } else {
            $this->sort_order = (int) Project::max('sort_order') + 1;
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

    public function addService(int $id): void
    {
        if (! in_array($id, $this->selectedServiceIds, true)) {
            $this->selectedServiceIds[] = $id;
        }
        $this->serviceSearch = '';
    }

    public function removeService(int $id): void
    {
        $this->selectedServiceIds = array_values(array_filter($this->selectedServiceIds, fn ($v) => $v !== $id));
    }

    public function removeGalleryImage(int $mediaId): void
    {
        $this->project?->media()->where('collection_name', 'gallery')->whereKey($mediaId)->first()?->delete();
    }

    /** @param array<int, int|string> $ids */
    public function reorderGallery(array $ids): void
    {
        Media::setNewOrder(array_map('intval', $ids));
    }

    public function removeProjectImage(): void
    {
        $this->removeImage = true;
    }

    public function save(): void
    {
        $this->authorize($this->project ? 'projects.update' : 'projects.create');

        try {
            $this->validate([
                'title.ar' => ['required', 'string', 'max:255'],
                'title.en' => ['required', 'string', 'max:255'],
                'slug.ar' => ['required', 'string', 'max:255'],
                'slug.en' => ['required', 'string', 'max:255'],
                'status' => ['required', 'in:current,completed'],
                'type' => ['required', 'in:residential,commercial,outdoor,smart_home,hospitality'],
                'stage' => ['required', 'in:design,first_fix,installation,commissioning,handover'],
                'progress' => ['integer', 'min:0', 'max:100'],
                'sort_order' => ['integer', 'min:0'],
                'location.ar' => ['nullable', 'string', 'max:255'],
                'location.en' => ['nullable', 'string', 'max:255'],
                'client.ar' => ['nullable', 'string', 'max:255'],
                'client.en' => ['nullable', 'string', 'max:255'],
                'phase_label.ar' => ['nullable', 'string', 'max:255'],
                'phase_label.en' => ['nullable', 'string', 'max:255'],
                'scope_summary.ar' => ['nullable', 'string'],
                'scope_summary.en' => ['nullable', 'string'],
                'about.ar' => ['nullable', 'string'],
                'about.en' => ['nullable', 'string'],
                'challenge.ar' => ['nullable', 'string'],
                'challenge.en' => ['nullable', 'string'],
                'solution.ar' => ['nullable', 'string'],
                'solution.en' => ['nullable', 'string'],
                'result.ar' => ['nullable', 'string'],
                'result.en' => ['nullable', 'string'],
                'meta_title.ar' => ['nullable', 'string', 'max:255'],
                'meta_title.en' => ['nullable', 'string', 'max:255'],
                'meta_description.ar' => ['nullable', 'string', 'max:500'],
                'meta_description.en' => ['nullable', 'string', 'max:500'],
                'start_date' => ['nullable', 'date'],
                'expected_completion' => ['nullable', 'date'],
                'completed_year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
                'imageUpload' => ['nullable', 'image', 'max:5120'],
                'galleryUploads.*' => ['image', 'max:5120'],
                'selectedProductIds.*' => ['integer'],
                'selectedServiceIds.*' => ['integer'],
            ]);
        } catch (ValidationException $e) {
            $this->activeTab = $this->tabForField(array_key_first($e->errors()) ?? 'title.ar');
            $this->dispatch('toast', tone: 'error', title: __('admin.projects.has_errors'));
            throw $e;
        }

        $project = $this->project ?? new Project;
        $project->fill([
            'title' => $this->title,
            'slug' => $this->slug,
            'status' => $this->status,
            'type' => $this->type,
            'location' => $this->location,
            'client' => $this->client,
            'stage' => $this->stage,
            'progress' => $this->progress,
            'phase_label' => $this->phase_label,
            'scope_summary' => $this->scope_summary,
            'about' => Html::cleanTranslations($this->about),
            'challenge' => $this->challenge,
            'solution' => $this->solution,
            'result' => $this->result,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'start_date' => $this->start_date !== '' ? $this->start_date : null,
            'expected_completion' => $this->expected_completion !== '' ? $this->expected_completion : null,
            'completed_year' => $this->completed_year !== '' ? (int) $this->completed_year : null,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'sort_order' => $this->sort_order,
        ]);
        $project->save();

        if ($this->imageUpload instanceof TemporaryUploadedFile) {
            $project->clearMediaCollection('cover');
            $project->addMedia($this->imageUpload->getRealPath())
                ->usingFileName($this->imageUpload->getClientOriginalName())
                ->toMediaCollection('cover');
        } elseif ($this->removeImage) {
            $project->clearMediaCollection('cover');
        }

        foreach ($this->galleryUploads as $upload) {
            if ($upload instanceof TemporaryUploadedFile) {
                $project->addMedia($upload->getRealPath())
                    ->usingFileName($upload->getClientOriginalName())
                    ->toMediaCollection('gallery');
            }
        }
        $this->galleryUploads = [];

        $project->products()->sync($this->selectedProductIds);
        $project->services()->sync($this->selectedServiceIds);

        $this->dispatch('toast', tone: 'success', title: __('admin.projects.saved'));
        $this->redirectRoute('admin.projects.edit', $project, navigate: true);
    }

    private function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'title'),
            str_starts_with($field, 'slug'),
            str_starts_with($field, 'type'),
            str_starts_with($field, 'location'),
            str_starts_with($field, 'client') => 'basic',
            str_starts_with($field, 'status'),
            str_starts_with($field, 'stage'),
            str_starts_with($field, 'progress'),
            str_starts_with($field, 'phase_label'),
            str_starts_with($field, 'start_date'),
            str_starts_with($field, 'expected_completion'),
            str_starts_with($field, 'completed_year'),
            str_starts_with($field, 'scope_summary') => 'details',
            str_starts_with($field, 'about'),
            str_starts_with($field, 'challenge'),
            str_starts_with($field, 'solution'),
            str_starts_with($field, 'result') => 'content',
            str_starts_with($field, 'selectedProduct'),
            str_starts_with($field, 'selectedService') => 'links',
            str_starts_with($field, 'galleryUploads') => 'gallery',
            str_starts_with($field, 'meta_title'),
            str_starts_with($field, 'meta_description') => 'seo',
            default => 'basic',
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

    /** @return array<int, array{id: int, url: string}> */
    public function galleryItems(): array
    {
        if (! $this->project) {
            return [];
        }

        return $this->project->getMedia('gallery')
            ->map(fn ($m) => ['id' => (int) $m->id, 'url' => $m->getUrl()])->all();
    }

    /** @return Collection<int, Product> */
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

    /** @return Collection<int, Service> */
    public function serviceSearchResults()
    {
        if ($this->serviceSearch === '') {
            return collect();
        }
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], trim($this->serviceSearch)).'%';

        return Service::query()
            ->where(fn (Builder $q) => $q
                ->where('title->ar', 'like', $like)
                ->orWhere('title->en', 'like', $like))
            ->whereNotIn('id', $this->selectedServiceIds ?: [0])
            ->limit(8)
            ->get();
    }

    /** @return Collection<int, Product> */
    public function selectedProducts()
    {
        if (empty($this->selectedProductIds)) {
            return collect();
        }

        return Product::whereIn('id', $this->selectedProductIds)->get();
    }

    /** @return Collection<int, Service> */
    public function selectedServices()
    {
        if (empty($this->selectedServiceIds)) {
            return collect();
        }

        return Service::whereIn('id', $this->selectedServiceIds)->get();
    }

    public function deleteProject(): void
    {
        $this->authorize('projects.delete');
        $this->project?->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.projects.deleted'));
        $this->redirectRoute('admin.projects', navigate: true);
    }

    public function render(): View
    {
        $imageUrl = null;
        if ($this->imageUpload instanceof TemporaryUploadedFile) {
            $imageUrl = $this->imageUpload->temporaryUrl();
        } elseif (! $this->removeImage) {
            $url = $this->project?->getFirstMediaUrl('cover');
            $imageUrl = ($url !== '' && $url !== null) ? $url : null;
        }

        return view('livewire.admin.projects.edit', [
            'imageUrl' => $imageUrl,
            'galleryItems' => $this->galleryItems(),
            'errorTabs' => $this->errorTabs(),
            'productResults' => $this->productSearchResults(),
            'serviceResults' => $this->serviceSearchResults(),
            'pickedProducts' => $this->selectedProducts(),
            'pickedServices' => $this->selectedServices(),
        ]);
    }
}

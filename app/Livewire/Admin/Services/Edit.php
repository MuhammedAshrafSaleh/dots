<?php

namespace App\Livewire\Admin\Services;

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Models\Service;
use App\Support\Html;
use Illuminate\Contracts\View\View;
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

    public ?Service $service = null;

    public string $activeTab = 'basic';

    /** @var array<string, string> */
    public array $title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $slug = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $number = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $excerpt = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $included_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $duration_text = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['ar' => '', 'en' => ''];

    public bool $is_active = true;

    public bool $show_in_footer = false;

    public int $sort_order = 0;

    // Included items (service_items)
    /** @var array<string, array{id: int|null, text: array<string,string>}> */
    public array $items = [];

    // FAQ
    /** @var array<string, array{id: int|null, question: array<string,string>, answer: array<string,string>}> */
    public array $faqs = [];

    // Gallery uploads (new files picked this session)
    /** @var array<int, mixed> */
    public array $galleryUploads = [];

    // Cover image
    public mixed $imageUpload = null;

    public bool $removeImage = false;

    public function mount(?Service $service = null): void
    {
        if ($service?->exists) {
            $this->service = $service;

            foreach (['title', 'slug', 'excerpt', 'description', 'included_title', 'duration_text', 'meta_title', 'meta_description'] as $f) {
                $this->{$f} = [
                    'ar' => $service->getTranslation($f, 'ar', false),
                    'en' => $service->getTranslation($f, 'en', false),
                ];
            }

            // number is stored as a plain string in the DB (not translatable per ERD)
            $this->number = ['ar' => '', 'en' => (string) $service->number];

            $this->is_active = (bool) $service->is_active;
            $this->show_in_footer = (bool) $service->show_in_footer;
            $this->sort_order = (int) $service->sort_order;

            foreach ($service->items as $item) {
                $key = (string) $item->id;
                $this->items[$key] = [
                    'id' => $item->id,
                    'text' => [
                        'ar' => $item->getTranslation('text', 'ar', false),
                        'en' => $item->getTranslation('text', 'en', false),
                    ],
                ];
            }

            foreach ($service->faqs as $faq) {
                $key = (string) $faq->id;
                $this->faqs[$key] = [
                    'id' => $faq->id,
                    'question' => [
                        'ar' => $faq->getTranslation('question', 'ar', false),
                        'en' => $faq->getTranslation('question', 'en', false),
                    ],
                    'answer' => [
                        'ar' => $faq->getTranslation('answer', 'ar', false),
                        'en' => $faq->getTranslation('answer', 'en', false),
                    ],
                ];
            }
        } else {
            $this->sort_order = (int) Service::max('sort_order') + 1;
        }
    }

    public function generateSlugs(): void
    {
        $this->fillSlugsFrom('title');
    }

    // --- Items ---

    public function addItem(): void
    {
        $key = 'new_'.uniqid();
        $this->items[$key] = ['id' => null, 'text' => ['ar' => '', 'en' => '']];
    }

    public function removeItem(string $key): void
    {
        unset($this->items[$key]);
    }

    /** @param array<int, string> $keys */
    public function reorderItems(array $keys): void
    {
        $reordered = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->items)) {
                $reordered[$key] = $this->items[$key];
            }
        }
        $this->items = $reordered;
    }

    // --- FAQs ---

    public function addFaq(): void
    {
        $key = 'new_'.uniqid();
        $this->faqs[$key] = ['id' => null, 'question' => ['ar' => '', 'en' => ''], 'answer' => ['ar' => '', 'en' => '']];
    }

    public function removeFaq(string $key): void
    {
        unset($this->faqs[$key]);
    }

    /** @param array<int, string> $keys */
    public function reorderFaqs(array $keys): void
    {
        $reordered = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->faqs)) {
                $reordered[$key] = $this->faqs[$key];
            }
        }
        $this->faqs = $reordered;
    }

    // --- Gallery ---

    public function removeGalleryImage(int $mediaId): void
    {
        $this->service?->media()->where('collection_name', 'gallery')->whereKey($mediaId)->first()?->delete();
    }

    /** @param array<int, int|string> $ids */
    public function reorderGallery(array $ids): void
    {
        Media::setNewOrder(array_map('intval', $ids));
    }

    public function removeServiceImage(): void
    {
        $this->removeImage = true;
    }

    // --- Save ---

    public function save(): void
    {
        $this->authorize($this->service ? 'services.update' : 'services.create');

        try {
            $this->validate([
                'title.ar' => ['required', 'string', 'max:255'],
                'title.en' => ['required', 'string', 'max:255'],
                'slug.ar' => ['required', 'string', 'max:255'],
                'slug.en' => ['required', 'string', 'max:255'],
                'excerpt.ar' => ['nullable', 'string'],
                'excerpt.en' => ['nullable', 'string'],
                'description.ar' => ['nullable', 'string'],
                'description.en' => ['nullable', 'string'],
                'included_title.ar' => ['nullable', 'string', 'max:255'],
                'included_title.en' => ['nullable', 'string', 'max:255'],
                'duration_text.ar' => ['nullable', 'string', 'max:255'],
                'duration_text.en' => ['nullable', 'string', 'max:255'],
                'meta_title.ar' => ['nullable', 'string', 'max:255'],
                'meta_title.en' => ['nullable', 'string', 'max:255'],
                'meta_description.ar' => ['nullable', 'string', 'max:500'],
                'meta_description.en' => ['nullable', 'string', 'max:500'],
                'sort_order' => ['integer', 'min:0'],
                'imageUpload' => ['nullable', 'image', 'max:5120'],
                'galleryUploads.*' => ['image', 'max:5120'],
                'items.*.text.ar' => ['nullable', 'string', 'max:500'],
                'items.*.text.en' => ['nullable', 'string', 'max:500'],
                'faqs.*.question.ar' => ['nullable', 'string', 'max:500'],
                'faqs.*.question.en' => ['nullable', 'string', 'max:500'],
                'faqs.*.answer.ar' => ['nullable', 'string'],
                'faqs.*.answer.en' => ['nullable', 'string'],
            ]);
        } catch (ValidationException $e) {
            $this->activeTab = $this->tabForField(array_key_first($e->errors()) ?? 'title.ar');
            $this->dispatch('toast', tone: 'error', title: __('admin.services.has_errors'));
            throw $e;
        }

        $service = $this->service ?? new Service;
        $service->fill([
            'number' => $this->number['en'],
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'description' => Html::cleanTranslations($this->description),
            'included_title' => $this->included_title,
            'duration_text' => $this->duration_text,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_active' => $this->is_active,
            'show_in_footer' => $this->show_in_footer,
            'sort_order' => $this->sort_order,
        ]);
        $service->save();

        // Cover image
        if ($this->imageUpload instanceof TemporaryUploadedFile) {
            $service->clearMediaCollection('image');
            $service->addMedia($this->imageUpload->getRealPath())
                ->usingFileName($this->imageUpload->getClientOriginalName())
                ->toMediaCollection('image');
        } elseif ($this->removeImage) {
            $service->clearMediaCollection('image');
        }

        // Gallery uploads
        foreach ($this->galleryUploads as $upload) {
            if ($upload instanceof TemporaryUploadedFile) {
                $service->addMedia($upload->getRealPath())
                    ->usingFileName($upload->getClientOriginalName())
                    ->toMediaCollection('gallery');
            }
        }
        $this->galleryUploads = [];

        // Sync items
        $existingIds = $service->items()->pluck('id')->all();
        $seenIds = [];
        $order = 0;
        foreach ($this->items as $row) {
            $id = $row['id'] ?? null;
            if ($id) {
                $item = $service->items()->find($id);
                if ($item) {
                    $item->update(['text' => $row['text'], 'sort_order' => $order++]);
                    $seenIds[] = $id;

                    continue;
                }
            }
            $item = $service->items()->create(['text' => $row['text'], 'sort_order' => $order++]);
            $seenIds[] = $item->id;
        }
        $service->items()->whereNotIn('id', $seenIds)->delete();

        // Sync FAQs
        $existingFaqIds = $service->faqs()->pluck('id')->all();
        $seenFaqIds = [];
        $order = 0;
        foreach ($this->faqs as $row) {
            $id = $row['id'] ?? null;
            if ($id) {
                $faq = $service->faqs()->find($id);
                if ($faq) {
                    $faq->update(['question' => $row['question'], 'answer' => $row['answer'], 'sort_order' => $order++]);
                    $seenFaqIds[] = $id;

                    continue;
                }
            }
            $faq = $service->faqs()->create([
                'question' => $row['question'],
                'answer' => $row['answer'],
                'sort_order' => $order++,
            ]);
            $seenFaqIds[] = $faq->id;
        }
        $service->faqs()->whereNotIn('id', $seenFaqIds)->delete();

        $this->dispatch('toast', tone: 'success', title: __('admin.services.saved'));
        $this->redirectRoute('admin.services.edit', $service, navigate: true);
    }

    private function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'title'),
            str_starts_with($field, 'slug'),
            str_starts_with($field, 'excerpt'),
            str_starts_with($field, 'description'),
            str_starts_with($field, 'included_title'),
            str_starts_with($field, 'duration_text'),
            str_starts_with($field, 'number') => 'basic',
            str_starts_with($field, 'items') => 'items',
            str_starts_with($field, 'galleryUploads') => 'gallery',
            str_starts_with($field, 'faqs') => 'faq',
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
        if (! $this->service) {
            return [];
        }

        return $this->service->getMedia('gallery')
            ->map(fn ($m) => ['id' => (int) $m->id, 'url' => $m->getUrl()])->all();
    }

    public function deleteService(): void
    {
        $this->authorize('services.delete');
        $this->service?->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.services.deleted'));
        $this->redirectRoute('admin.services', navigate: true);
    }

    public function render(): View
    {
        $imageUrl = null;
        if ($this->imageUpload instanceof TemporaryUploadedFile) {
            $imageUrl = $this->imageUpload->temporaryUrl();
        } elseif (! $this->removeImage) {
            $url = $this->service?->getFirstMediaUrl('image');
            $imageUrl = ($url !== '' && $url !== null) ? $url : null;
        }

        return view('livewire.admin.services.edit', [
            'imageUrl' => $imageUrl,
            'galleryItems' => $this->galleryItems(),
            'errorTabs' => $this->errorTabs(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin\Brands;

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Models\Brand;
use App\Support\Slug;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use InteractsWithAdminForms;
    use WithFileUploads;

    public ?Brand $brand = null;

    public string $name = '';

    public string $slug = '';

    /** @var array<string, string> */
    public array $origin_country = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $description = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $feature_text = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $catalogue_label = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['ar' => '', 'en' => ''];

    public bool $is_active = true;

    public bool $is_featured = false;

    public bool $is_authorised = false;

    public bool $show_in_partners = false;

    public bool $show_on_home = false;

    public int $sort_order = 0;

    // Temp uploads (null unless the admin picks a new file this session).
    public mixed $logoUpload = null;

    public mixed $coverUpload = null;

    public mixed $featureUpload = null;

    public mixed $catalogueUpload = null;

    /** @var array<string, bool> */
    public array $remove = ['logo' => false, 'cover' => false, 'feature' => false, 'catalogue' => false];

    public function mount(?Brand $brand = null): void
    {
        if ($brand?->exists) {
            $this->brand = $brand;
            $this->name = $brand->name;
            $this->slug = $brand->slug;
            foreach (['origin_country', 'description', 'feature_text', 'catalogue_label', 'meta_title', 'meta_description'] as $f) {
                $this->{$f} = ['ar' => $brand->getTranslation($f, 'ar', false), 'en' => $brand->getTranslation($f, 'en', false)];
            }
            $this->is_active = (bool) $brand->is_active;
            $this->is_featured = (bool) $brand->is_featured;
            $this->is_authorised = (bool) $brand->is_authorised;
            $this->show_in_partners = (bool) $brand->show_in_partners;
            $this->show_on_home = (bool) $brand->show_on_home;
            $this->sort_order = (int) $brand->sort_order;
        } else {
            $this->sort_order = (int) Brand::max('sort_order') + 1;
        }
    }

    public function generateSlug(): void
    {
        if (trim($this->name) !== '') {
            $this->slug = Slug::make($this->name, 'en');
        }
    }

    public function removeMedia(string $collection): void
    {
        if (array_key_exists($collection, $this->remove)) {
            $this->remove[$collection] = true;
        }
    }

    public function save(): void
    {
        $this->authorize($this->brand ? 'brands.update' : 'brands.create');

        $this->slug = Str::lower(trim($this->slug !== '' ? $this->slug : $this->name));

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'alpha_dash', Rule::unique('brands', 'slug')->ignore($this->brand?->id)],
            'origin_country.ar' => ['nullable', 'string', 'max:255'],
            'origin_country.en' => ['nullable', 'string', 'max:255'],
            'description.ar' => ['nullable', 'string'],
            'description.en' => ['nullable', 'string'],
            'feature_text.ar' => ['nullable', 'string'],
            'feature_text.en' => ['nullable', 'string'],
            'catalogue_label.ar' => ['nullable', 'string', 'max:255'],
            'catalogue_label.en' => ['nullable', 'string', 'max:255'],
            'meta_title.ar' => ['nullable', 'string', 'max:255'],
            'meta_title.en' => ['nullable', 'string', 'max:255'],
            'meta_description.ar' => ['nullable', 'string', 'max:500'],
            'meta_description.en' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['integer', 'min:0'],
            'logoUpload' => ['nullable', 'image', 'max:5120'],
            'coverUpload' => ['nullable', 'image', 'max:5120'],
            'featureUpload' => ['nullable', 'image', 'max:5120'],
            'catalogueUpload' => ['nullable', 'file', 'mimes:pdf', 'max:25600'],
        ]);

        $brand = $this->brand ?? new Brand;
        $brand->fill([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'origin_country' => $this->origin_country,
            'description' => $this->description,
            'feature_text' => $this->feature_text,
            'catalogue_label' => $this->catalogue_label,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_active' => $this->is_active,
            'is_featured' => $this->is_featured,
            'is_authorised' => $this->is_authorised,
            'show_in_partners' => $this->show_in_partners,
            'show_on_home' => $this->show_on_home,
            'sort_order' => $this->sort_order,
        ]);
        $brand->save();

        $this->syncMedia($brand, 'logo', $this->logoUpload);
        $this->syncMedia($brand, 'cover', $this->coverUpload);
        $this->syncMedia($brand, 'feature', $this->featureUpload);
        $this->syncMedia($brand, 'catalogue', $this->catalogueUpload);

        $this->dispatch('toast', tone: 'success', title: __('admin.brands.saved'), message: $brand->name);
        $this->redirectRoute('admin.brands', navigate: true);
    }

    private function syncMedia(Brand $brand, string $collection, mixed $upload): void
    {
        if ($upload instanceof TemporaryUploadedFile) {
            $brand->clearMediaCollection($collection);
            $brand->addMedia($upload->getRealPath())
                ->usingFileName($upload->getClientOriginalName())
                ->toMediaCollection($collection);
        } elseif ($this->remove[$collection] ?? false) {
            $brand->clearMediaCollection($collection);
        }
    }

    public function render(): View
    {
        return view('livewire.admin.brands.edit');
    }
}

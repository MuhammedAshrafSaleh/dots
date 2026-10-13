<?php

namespace App\Livewire\Admin\ContentPages;

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Models\Page;
use App\Support\Html;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use InteractsWithAdminForms;

    public ?Page $page = null;

    public string $activeTab = 'basic';

    public string $key = '';

    /** @var array<string, string> */
    public array $title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $slug = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $lede = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $body = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_title = ['ar' => '', 'en' => ''];

    /** @var array<string, string> */
    public array $meta_description = ['ar' => '', 'en' => ''];

    public bool $is_active = true;

    public bool $show_in_footer = false;

    public int $sort_order = 0;

    public function mount(?Page $page = null): void
    {
        if ($page?->exists) {
            $this->page = $page;
            $this->key = $page->key;

            foreach (['title', 'slug', 'lede', 'body', 'meta_title', 'meta_description'] as $f) {
                $this->{$f} = [
                    'ar' => $page->getTranslation($f, 'ar', false),
                    'en' => $page->getTranslation($f, 'en', false),
                ];
            }

            $this->is_active = (bool) $page->is_active;
            $this->show_in_footer = (bool) $page->show_in_footer;
            $this->sort_order = (int) $page->sort_order;
        } else {
            $this->sort_order = (int) Page::max('sort_order') + 1;
        }
    }

    public function generateSlugs(): void
    {
        $this->fillSlugsFrom('title');
    }

    public function save(): void
    {
        $this->authorize($this->page ? 'content_pages.update' : 'content_pages.create');

        try {
            $keyRules = $this->page
                ? ['nullable']
                : ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/', Rule::unique('pages', 'key')];

            $this->validate([
                'key' => $keyRules,
                'title.ar' => ['required', 'string', 'max:255'],
                'title.en' => ['required', 'string', 'max:255'],
                'slug.ar' => ['required', 'string', 'max:255'],
                'slug.en' => ['required', 'string', 'max:255'],
                'lede.ar' => ['nullable', 'string', 'max:500'],
                'lede.en' => ['nullable', 'string', 'max:500'],
                'body.ar' => ['nullable', 'string'],
                'body.en' => ['nullable', 'string'],
                'meta_title.ar' => ['nullable', 'string', 'max:255'],
                'meta_title.en' => ['nullable', 'string', 'max:255'],
                'meta_description.ar' => ['nullable', 'string', 'max:500'],
                'meta_description.en' => ['nullable', 'string', 'max:500'],
                'sort_order' => ['integer', 'min:0'],
            ]);
        } catch (ValidationException $e) {
            $this->activeTab = $this->tabForField(array_key_first($e->errors()) ?? 'title.ar');
            $this->dispatch('toast', tone: 'error', title: __('admin.content_pages.has_errors'));
            throw $e;
        }

        $page = $this->page ?? new Page;
        $page->fill([
            'key' => $this->page ? $this->page->key : $this->key,
            'title' => $this->title,
            'slug' => $this->slug,
            'lede' => $this->lede,
            'body' => Html::cleanTranslations($this->body),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'is_active' => $this->is_active,
            'show_in_footer' => $this->show_in_footer,
            'sort_order' => $this->sort_order,
        ]);
        $page->save();

        $this->dispatch('toast', tone: 'success', title: __('admin.content_pages.saved'));
        $this->redirectRoute('admin.content-pages.edit', $page, navigate: true);
    }

    public function deletePage(): void
    {
        $this->authorize('content_pages.delete');
        $this->page?->delete();
        $this->dispatch('toast', tone: 'success', title: __('admin.content_pages.deleted'));
        $this->redirectRoute('admin.content-pages', navigate: true);
    }

    private function tabForField(string $field): string
    {
        return match (true) {
            str_starts_with($field, 'key'),
            str_starts_with($field, 'title'),
            str_starts_with($field, 'slug'),
            str_starts_with($field, 'lede') => 'basic',
            str_starts_with($field, 'body') => 'body',
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

    public function render(): View
    {
        return view('livewire.admin.content-pages.edit', [
            'errorTabs' => $this->errorTabs(),
        ]);
    }
}

<?php

namespace App\Livewire\Admin\PageContent;

use App\Livewire\Admin\Concerns\InteractsWithAdminForms;
use App\Models\PageSection;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    use InteractsWithAdminForms;
    use WithFileUploads;

    public string $page = 'home';

    /** @var array<string, array{is_active: bool, content: array<string, mixed>}> */
    public array $form = [];

    /** @var array<string, mixed> upload slot key → TemporaryUploadedFile|null */
    public array $uploads = [];

    /** @var array<string, bool> slots flagged for removal on save */
    public array $removeMedia = [];

    /** @return list<array{string, string}> */
    public function pages(): array
    {
        return [
            ['home',     __('admin.page_sections.pages.home')],
            ['about',    __('admin.page_sections.pages.about')],
            ['services', __('admin.page_sections.pages.services')],
            ['brands',   __('admin.page_sections.pages.brands')],
            ['projects', __('admin.page_sections.pages.projects')],
            ['blog',     __('admin.page_sections.pages.blog')],
            ['global',   __('admin.page_sections.pages.global')],
        ];
    }

    public function mount(string $page = 'home'): void
    {
        abort_if(! array_key_exists($page, config('page_sections', [])), 404);
        $this->page = $page;
        $this->initForm();
    }

    private function initForm(): void
    {
        /** @var array<string, mixed> $schema */
        $schema = config("page_sections.{$this->page}", []);
        $rows   = PageSection::where('page', $this->page)->get()->keyBy('key');

        foreach ($schema as $sectionKey => $fields) {
            /** @var array<string, mixed> $fields */
            $row     = $rows->get($sectionKey);
            $existing = is_array($row?->content) ? $row->content : [];

            $this->form[$sectionKey] = [
                'is_active' => $row ? (bool) $row->is_active : true,
                'content'   => $this->initContent($fields, $existing),
            ];
        }

        // Pre-allocate upload + remove slots for every media field.
        foreach ($schema as $sectionKey => $fields) {
            /** @var array<string, mixed> $fields */
            foreach ($fields as $fieldName => $spec) {
                $type = is_array($spec) ? ($spec['type'] ?? null) : $spec;

                if ($type === 'media') {
                    $this->uploads["{$sectionKey}__{$fieldName}"]    = null;
                    $this->removeMedia["{$sectionKey}__{$fieldName}"] = false;
                } elseif ($type === 'media_many') {
                    $count = is_array($spec) ? ($spec['count'] ?? 1) : 1;
                    for ($i = 0; $i < $count; $i++) {
                        $slot = "{$sectionKey}__{$fieldName}_{$i}";
                        $this->uploads[$slot]    = null;
                        $this->removeMedia[$slot] = false;
                    }
                } elseif ($type === 'list') {
                    $listCount  = is_array($spec) ? ($spec['count'] ?? 0) : 0;
                    $listFields = is_array($spec) ? ($spec['fields'] ?? []) : [];
                    /** @var array<string, mixed> $listFields */
                    foreach ($listFields as $sf => $st) {
                        if ($st === 'media') {
                            for ($i = 0; $i < $listCount; $i++) {
                                $slot = "{$sectionKey}__item_{$sf}_{$i}";
                                $this->uploads[$slot]    = null;
                                $this->removeMedia[$slot] = false;
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fields
     * @param  array<string, mixed>  $existing
     * @return array<string, mixed>
     */
    private function initContent(array $fields, array $existing): array
    {
        $content = [];

        foreach ($fields as $fieldName => $spec) {
            $type = is_array($spec) ? ($spec['type'] ?? null) : $spec;

            if (in_array($type, ['media', 'media_many'], true)) {
                continue;
            }

            $val = $existing[$fieldName] ?? null;

            if ($type === 'text') {
                $content[$fieldName] = [
                    'ar' => is_array($val) ? ($val['ar'] ?? '') : '',
                    'en' => is_array($val) ? ($val['en'] ?? '') : '',
                ];
            } elseif ($type === 'list') {
                $count      = is_array($spec) ? ($spec['count'] ?? 0) : 0;
                $listFields = is_array($spec) ? ($spec['fields'] ?? []) : [];
                $rawItems   = is_array($val) ? $val : [];
                $items      = [];

                for ($i = 0; $i < $count; $i++) {
                    $raw  = is_array($rawItems[$i] ?? null) ? $rawItems[$i] : [];
                    $item = [];

                    /** @var array<string, mixed> $listFields */
                    foreach ($listFields as $sf => $st) {
                        if ($st === 'media') {
                            continue;
                        }
                        $sv = $raw[$sf] ?? null;
                        $item[$sf] = $st === 'text'
                            ? ['ar' => is_array($sv) ? ($sv['ar'] ?? '') : '', 'en' => is_array($sv) ? ($sv['en'] ?? '') : '']
                            : (is_string($sv) ? $sv : '');
                    }

                    $items[] = $item;
                }

                $content[$fieldName] = $items;
            } else {
                // link, icon
                $content[$fieldName] = is_string($val) ? $val : '';
            }
        }

        return $content;
    }

    public function removeMediaSlot(string $slot): void
    {
        if (array_key_exists($slot, $this->removeMedia)) {
            $this->removeMedia[$slot] = true;
            $this->uploads[$slot]     = null;
        }
    }

    public function save(): void
    {
        $this->authorize('page_content.update');

        $this->validate($this->buildRules());

        /** @var array<string, mixed> $schema */
        $schema = config("page_sections.{$this->page}", []);
        $rows   = PageSection::where('page', $this->page)->get()->keyBy('key');

        foreach ($schema as $sectionKey => $fields) {
            /** @var array<string, mixed> $fields */
            $row            = $rows->get($sectionKey) ?? new PageSection;
            $row->page      = $this->page;
            $row->key       = $sectionKey;
            $row->is_active = (bool) ($this->form[$sectionKey]['is_active'] ?? true);
            $row->content   = $this->form[$sectionKey]['content'] ?? [];
            $row->save();

            $this->syncSectionMedia($row, $sectionKey, $fields);
        }

        $this->resetUploadsAndRemoves();

        $this->dispatch('toast', tone: 'success', title: __('admin.page_sections.saved'));
    }

    /** @return array<string, mixed> */
    private function buildRules(): array
    {
        $rules  = [];
        /** @var array<string, mixed> $schema */
        $schema = config("page_sections.{$this->page}", []);

        foreach ($schema as $sectionKey => $fields) {
            /** @var array<string, mixed> $fields */
            $rules["form.{$sectionKey}.is_active"] = ['boolean'];

            foreach ($fields as $fieldName => $spec) {
                $type = is_array($spec) ? ($spec['type'] ?? null) : $spec;

                if (in_array($type, ['media', 'media_many'], true)) {
                    continue;
                }

                if ($type === 'text') {
                    $rules["form.{$sectionKey}.content.{$fieldName}.ar"] = ['nullable', 'string', 'max:2000'];
                    $rules["form.{$sectionKey}.content.{$fieldName}.en"] = ['nullable', 'string', 'max:2000'];
                } elseif ($type === 'list') {
                    $count      = is_array($spec) ? ($spec['count'] ?? 0) : 0;
                    $listFields = is_array($spec) ? ($spec['fields'] ?? []) : [];

                    for ($i = 0; $i < $count; $i++) {
                        /** @var array<string, mixed> $listFields */
                        foreach ($listFields as $sf => $st) {
                            if ($st === 'media') {
                                continue;
                            }
                            if ($st === 'text') {
                                $rules["form.{$sectionKey}.content.{$fieldName}.{$i}.{$sf}.ar"] = ['nullable', 'string', 'max:2000'];
                                $rules["form.{$sectionKey}.content.{$fieldName}.{$i}.{$sf}.en"] = ['nullable', 'string', 'max:2000'];
                            } else {
                                $rules["form.{$sectionKey}.content.{$fieldName}.{$i}.{$sf}"] = ['nullable', 'string', 'max:500'];
                            }
                        }
                    }
                } else {
                    $rules["form.{$sectionKey}.content.{$fieldName}"] = ['nullable', 'string', 'max:1000'];
                }
            }
        }

        foreach (array_keys($this->uploads) as $slot) {
            $rules["uploads.{$slot}"] = ['nullable', 'file', 'max:51200'];
        }

        return $rules;
    }

    /** @param array<string, mixed> $fields */
    private function syncSectionMedia(PageSection $row, string $sectionKey, array $fields): void
    {
        foreach ($fields as $fieldName => $spec) {
            $type = is_array($spec) ? ($spec['type'] ?? null) : $spec;

            if ($type === 'media') {
                $this->syncOneMediaSlot($row, "{$sectionKey}__{$fieldName}", $fieldName);
            } elseif ($type === 'media_many') {
                $count      = is_array($spec) ? ($spec['count'] ?? 1) : 1;
                $mediaItems = $row->getMedia('section')
                    ->filter(fn ($m) => $m->getCustomProperty('field') === $fieldName)
                    ->values();

                for ($i = 0; $i < $count; $i++) {
                    $slot   = "{$sectionKey}__{$fieldName}_{$i}";
                    $upload = $this->uploads[$slot] ?? null;
                    $remove = $this->removeMedia[$slot] ?? false;

                    if ($upload instanceof TemporaryUploadedFile) {
                        $mediaItems->get($i)?->delete();
                        $row->addMedia($upload->getRealPath())
                            ->withCustomProperties(['field' => $fieldName])
                            ->usingFileName($upload->getClientOriginalName())
                            ->toMediaCollection('section');
                    } elseif ($remove) {
                        $mediaItems->get($i)?->delete();
                    }
                }
            } elseif ($type === 'list') {
                $listCount  = is_array($spec) ? ($spec['count'] ?? 0) : 0;
                $listFields = is_array($spec) ? ($spec['fields'] ?? []) : [];

                /** @var array<string, mixed> $listFields */
                foreach ($listFields as $sf => $st) {
                    if ($st !== 'media') {
                        continue;
                    }
                    for ($i = 0; $i < $listCount; $i++) {
                        $this->syncOneMediaSlot($row, "{$sectionKey}__item_{$sf}_{$i}", "item_{$sf}_{$i}");
                    }
                }
            }
        }
    }

    private function syncOneMediaSlot(PageSection $row, string $slot, string $mediaField): void
    {
        $upload = $this->uploads[$slot] ?? null;
        $remove = $this->removeMedia[$slot] ?? false;

        if ($upload instanceof TemporaryUploadedFile) {
            $row->getMedia('section')
                ->filter(fn ($m) => $m->getCustomProperty('field') === $mediaField)
                ->each->delete();

            $row->addMedia($upload->getRealPath())
                ->withCustomProperties(['field' => $mediaField])
                ->usingFileName($upload->getClientOriginalName())
                ->toMediaCollection('section');
        } elseif ($remove) {
            $row->getMedia('section')
                ->filter(fn ($m) => $m->getCustomProperty('field') === $mediaField)
                ->each->delete();
        }
    }

    private function resetUploadsAndRemoves(): void
    {
        foreach (array_keys($this->uploads) as $slot) {
            $this->uploads[$slot]     = null;
            $this->removeMedia[$slot] = false;
        }
    }

    public function render(): View
    {
        /** @var array<string, mixed> $schema */
        $schema = config("page_sections.{$this->page}", []);
        $rows   = PageSection::where('page', $this->page)->with('media')->get()->keyBy('key');

        return view('livewire.admin.page-content.edit', [
            'pageSchema' => $schema,
            'pages'      => $this->pages(),
            'mediaUrls'  => $this->computeMediaUrls($schema, $rows),
        ]);
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  \Illuminate\Support\Collection<string, PageSection>  $rows
     * @return array<string, string|null>
     */
    private function computeMediaUrls(array $schema, $rows): array
    {
        $urls = [];

        foreach ($schema as $sectionKey => $fields) {
            /** @var array<string, mixed> $fields */
            $row = $rows->get($sectionKey);

            foreach ($fields as $fieldName => $spec) {
                $type = is_array($spec) ? ($spec['type'] ?? null) : $spec;

                if ($type === 'media') {
                    $slot        = "{$sectionKey}__{$fieldName}";
                    $urls[$slot] = $this->resolveMediaSlotUrl($slot, $row, $fieldName);
                } elseif ($type === 'media_many') {
                    $count      = is_array($spec) ? ($spec['count'] ?? 1) : 1;
                    $mediaItems = $row?->getMedia('section')
                        ->filter(fn ($m) => $m->getCustomProperty('field') === $fieldName)
                        ->values();

                    for ($i = 0; $i < $count; $i++) {
                        $slot   = "{$sectionKey}__{$fieldName}_{$i}";
                        $upload = $this->uploads[$slot] ?? null;

                        if ($upload instanceof TemporaryUploadedFile) {
                            try { $urls[$slot] = $upload->temporaryUrl(); } catch (\Throwable) { $urls[$slot] = null; }
                        } elseif ($this->removeMedia[$slot] ?? false) {
                            $urls[$slot] = null;
                        } else {
                            $urls[$slot] = $mediaItems?->get($i)?->getUrl() ?: null;
                        }
                    }
                } elseif ($type === 'list') {
                    $listCount  = is_array($spec) ? ($spec['count'] ?? 0) : 0;
                    $listFields = is_array($spec) ? ($spec['fields'] ?? []) : [];

                    /** @var array<string, mixed> $listFields */
                    foreach ($listFields as $sf => $st) {
                        if ($st !== 'media') {
                            continue;
                        }
                        for ($i = 0; $i < $listCount; $i++) {
                            $slot        = "{$sectionKey}__item_{$sf}_{$i}";
                            $urls[$slot] = $this->resolveMediaSlotUrl($slot, $row, "item_{$sf}_{$i}");
                        }
                    }
                }
            }
        }

        return $urls;
    }

    private function resolveMediaSlotUrl(string $slot, ?PageSection $row, string $mediaField): ?string
    {
        $upload = $this->uploads[$slot] ?? null;

        if ($upload instanceof TemporaryUploadedFile) {
            try { return $upload->temporaryUrl(); } catch (\Throwable) { return null; }
        }

        if ($this->removeMedia[$slot] ?? false) {
            return null;
        }

        $m = $row?->getMedia('section')
            ->first(fn ($m) => $m->getCustomProperty('field') === $mediaField);

        return $m?->getUrl() ?: null;
    }
}

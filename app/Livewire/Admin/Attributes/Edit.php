<?php

namespace App\Livewire\Admin\Attributes;

use App\Enums\AttributeDisplay;
use App\Models\Attribute;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.admin')]
class Edit extends Component
{
    public ?Attribute $attribute = null;

    public string $code = '';

    public string $unit = '';

    /** @var array<string, string> */
    public array $name = ['ar' => '', 'en' => ''];

    public string $display = 'chip';

    public bool $is_filterable = true;

    public bool $is_variant_axis = false;

    /** @var array<string, array{id: int|null, value: string, label: array<string, string>, swatch: string|null}> */
    public array $values = [];

    public function mount(?Attribute $attribute = null): void
    {
        if ($attribute?->exists) {
            $this->attribute = $attribute;
            $this->code = $attribute->code;
            $this->unit = (string) $attribute->unit;
            $this->name = ['ar' => $attribute->getTranslation('name', 'ar', false), 'en' => $attribute->getTranslation('name', 'en', false)];
            $this->display = $attribute->display->value;
            $this->is_filterable = (bool) $attribute->is_filterable;
            $this->is_variant_axis = (bool) $attribute->is_variant_axis;

            foreach ($attribute->values()->get() as $value) {
                $this->values[(string) Str::uuid()] = [
                    'id' => $value->id,
                    'value' => $value->value,
                    'label' => ['ar' => $value->getTranslation('label', 'ar', false), 'en' => $value->getTranslation('label', 'en', false)],
                    'swatch' => $value->swatch,
                ];
            }
        }
    }

    public function addValue(): void
    {
        $this->values[(string) Str::uuid()] = [
            'id' => null,
            'value' => '',
            'label' => ['ar' => '', 'en' => ''],
            'swatch' => $this->display === 'swatch' ? array_key_first(config('admin_swatches')) : null,
        ];
    }

    public function removeValue(string $key): void
    {
        unset($this->values[$key]);
    }

    /**
     * @param  array<int, string>  $keys
     */
    public function reorderValues(array $keys): void
    {
        $ordered = [];
        foreach ($keys as $key) {
            if (isset($this->values[$key])) {
                $ordered[$key] = $this->values[$key];
            }
        }
        $this->values = $ordered;
    }

    public function save(): void
    {
        $this->authorize($this->attribute ? 'attributes.update' : 'attributes.create');

        $this->code = Str::lower(trim($this->code));

        $data = $this->validate([
            'code' => ['required', 'string', 'alpha_dash', Rule::unique('attributes', 'code')->ignore($this->attribute?->id)],
            'unit' => ['nullable', 'string', 'max:16'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['required', 'string', 'max:255'],
            'display' => ['required', Rule::enum(AttributeDisplay::class)],
            'values' => ['array'],
            'values.*.value' => ['required', 'string', 'max:255'],
            'values.*.label.en' => ['required', 'string', 'max:255'],
            'values.*.label.ar' => ['required', 'string', 'max:255'],
        ]);

        $attribute = $this->attribute ?? new Attribute;
        $attribute->fill([
            'code' => $data['code'],
            'unit' => $data['unit'] !== '' ? $data['unit'] : null,
            'name' => $data['name'],
            'display' => $data['display'],
            'is_filterable' => $this->is_filterable,
            'is_variant_axis' => $this->is_variant_axis,
        ]);
        $attribute->save();

        // Sync values: upsert by id, delete removed, sort by current order.
        $keptIds = [];
        $order = 0;
        foreach ($this->values as $row) {
            $value = $row['id']
                ? $attribute->values()->whereKey($row['id'])->first() ?? $attribute->values()->make()
                : $attribute->values()->make();

            $value->fill([
                'value' => $row['value'],
                'label' => $row['label'],
                'swatch' => $this->display === 'swatch' ? ($row['swatch'] ?: null) : null,
                'sort_order' => $order++,
            ]);
            $value->attribute()->associate($attribute);
            $value->save();
            $keptIds[] = $value->id;
        }
        $attribute->values()->whereNotIn('id', $keptIds ?: [0])->delete();

        $this->dispatch('toast', tone: 'success', title: __('admin.attributes.saved'), message: $attribute->code);
        $this->redirectRoute('admin.attributes', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.admin.attributes.edit', [
            'swatches' => config('admin_swatches'),
        ]);
    }
}

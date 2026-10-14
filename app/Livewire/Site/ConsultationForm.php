<?php

namespace App\Livewire\Site;

use App\Enums\ConsultationAreaRange;
use App\Enums\ConsultationProjectType;
use App\Models\ConsultationRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ConsultationForm extends Component
{
    #[Locked]
    public ?int $serviceId = null;

    public string $name = '';

    public string $mobile = '';

    public string $projectType = '';

    public string $areaRange = '';

    public string $notes = '';

    /** Honeypot — invisible to humans; bots fill it. */
    public string $website = '';

    public bool $submitted = false;

    /** @return array<string, array<int, string>> */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'mobile' => ['required', 'string', 'regex:/^01[0125]\d{8}$/'],
            'projectType' => ['required', 'string', 'in:'.implode(',', array_column(ConsultationProjectType::cases(), 'value'))],
            'areaRange' => ['required', 'string', 'in:'.implode(',', array_column(ConsultationAreaRange::cases(), 'value'))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    protected function messages(): array
    {
        return [
            'mobile.regex' => __('ui.mobile_invalid'),
        ];
    }

    public function submit(): void
    {
        // Honeypot: silently succeed without saving
        if ($this->website !== '') {
            $this->submitted = true;

            return;
        }

        $key = 'consultation.'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            throw ValidationException::withMessages([
                'name' => __('ui.too_many_attempts'),
            ]);
        }

        // Strip spaces from mobile before validation
        $this->mobile = preg_replace('/\s+/', '', $this->mobile) ?? $this->mobile;

        $this->validate();

        ConsultationRequest::create([
            'service_id' => $this->serviceId,
            'name' => $this->name,
            'mobile' => $this->mobile,
            'project_type' => $this->projectType,
            'area_range' => $this->areaRange,
            'notes' => $this->notes ?: null,
            'locale' => app()->getLocale(),
            'ip' => request()->ip(),
            'status' => 'new',
        ]);

        RateLimiter::hit($key, 600);

        $this->submitted = true;
    }

    public function render(): View
    {
        return view('livewire.site.consultation-form', [
            'projectTypeOptions' => [
                ConsultationProjectType::Apartment->value => __('ui.project_type_apartment'),
                ConsultationProjectType::Villa->value => __('ui.project_type_villa'),
                ConsultationProjectType::Office->value => __('ui.project_type_office'),
                ConsultationProjectType::RetailFnb->value => __('ui.project_type_retail'),
                ConsultationProjectType::Hotel->value => __('ui.project_type_hotel'),
            ],
            'areaRangeOptions' => [
                ConsultationAreaRange::Lt150->value => '< 150 m²',
                ConsultationAreaRange::R150To400->value => '150–400 m²',
                ConsultationAreaRange::R400To1000->value => '400–1,000 m²',
                ConsultationAreaRange::Gt1000->value => '> 1,000 m²',
            ],
        ]);
    }
}

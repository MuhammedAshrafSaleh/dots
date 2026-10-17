<?php

namespace App\Models;

use App\Enums\ConsultationAreaRange;
use App\Enums\ConsultationProjectType;
use App\Enums\LeadStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property ConsultationProjectType $project_type
 * @property ConsultationAreaRange $area_range
 * @property LeadStatus $status
 */
class ConsultationRequest extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'project_type' => ConsultationProjectType::class,
            'area_range' => ConsultationAreaRange::class,
            'status' => LeadStatus::class,
        ];
    }

    /** @return BelongsTo<Service, $this> */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}

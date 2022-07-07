<?php

namespace App\Models\Pipeline;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, SoftDeletes};

/**
 * Class PipelineStage
 *
 * @property string|null $pipeline_id
 * @property string|null $stage_name
 * @property int|null $stage_order
 * @property float|null $stage_percentage
 *
 * @property-read string $qualified_stage_name
 * @property-read Pipeline|null $pipeline
 */
class PipelineStage extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function getQualifiedStageNameAttribute(): string
    {
        return sprintf("%s. %s", $this->stage_order, $this->stage_name);
    }
}

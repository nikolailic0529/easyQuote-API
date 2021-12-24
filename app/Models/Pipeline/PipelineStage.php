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
 */
class PipelineStage extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }
}

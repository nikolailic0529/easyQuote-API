<?php

namespace App\Models\Pipeline;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{Model, Relations\HasMany, SoftDeletes};

/**
 * Class Pipeline
 *
 * @property string|null $pipeline_name
 */
class Pipeline extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function pipelineStages(): HasMany
    {
        return $this->hasMany(PipelineStage::class);
    }
}

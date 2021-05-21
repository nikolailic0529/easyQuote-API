<?php

namespace App\Models\Pipeline;

use App\Models\Space;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany, SoftDeletes};

/**
 * Class Pipeline
 *
 * @property bool|null $is_system
 * @property bool|null $is_default
 * @property string|null $space_id
 * @property string|null $pipeline_name
 *
 * @property-read Space|null $space
 * @property-read \Illuminate\Database\Eloquent\Collection<PipelineStage>|PipelineStage[] $pipelineStages
 * @property-read OpportunityFormSchema|null $opportunityFormSchema
 */
class Pipeline extends Model
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function pipelineStages(): HasMany
    {
        return $this->hasMany(PipelineStage::class);
    }

    public function opportunityFormSchema(): BelongsTo
    {
        return $this->belongsTo(OpportunityFormSchema::class);
    }
}

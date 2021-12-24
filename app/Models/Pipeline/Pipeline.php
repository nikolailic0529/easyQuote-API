<?php

namespace App\Models\Pipeline;

use App\Contracts\SearchableEntity;
use App\Models\OpportunityForm\OpportunityForm;
use App\Models\Space;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\{Model, Relations\BelongsTo, Relations\HasMany, Relations\HasOne, SoftDeletes};

/**
 * Class Pipeline
 *
 * @property bool|null $is_system
 * @property bool|null $is_default
 * @property string|null $space_id
 * @property string|null $pipeline_name
 * @property int|null $pipeline_order
 *
 * @property-read Space|null $space
 * @property-read \Illuminate\Database\Eloquent\Collection<PipelineStage>|PipelineStage[] $pipelineStages
 * @property-read \App\Models\OpportunityForm\OpportunityForm|null $opportunityForm
 */
class Pipeline extends Model implements SearchableEntity
{
    use Uuid, SoftDeletes;

    protected $guarded = [];

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function pipelineStages(): HasMany
    {
        return $this->hasMany(PipelineStage::class)->orderBy('stage_order');
    }

    public function opportunityForm(): HasOne
    {
        return $this->hasOne(OpportunityForm::class);
    }

    public function getSearchIndex(): string
    {
        return $this->getTable();
    }

    public function toSearchArray(): array
    {
        return [
            'space_name' => $this->space->space_name,
            'pipeline_name' => $this->pipeline_name,
        ];
    }
}

<?php

namespace App\Domain\Pipeline\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use App\Domain\Space\Models\Space;
use App\Domain\Worldwide\Models\OpportunityForm;
use App\Foundation\Support\Elasticsearch\Contracts\SearchableEntity;
use Database\Factories\PipelineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class Pipeline.
 *
 * @property bool|null                                                               $is_system
 * @property bool|null                                                               $is_default
 * @property string|null                                                             $space_id
 * @property string|null                                                             $pipeline_name
 * @property int|null                                                                $pipeline_order
 * @property Space|null                                                              $space
 * @property \Illuminate\Database\Eloquent\Collection<PipelineStage>|PipelineStage[] $pipelineStages
 * @property \App\Domain\Worldwide\Models\OpportunityForm|null                       $opportunityForm
 */
class Pipeline extends Model implements SearchableEntity
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): PipelineFactory
    {
        return PipelineFactory::new();
    }

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

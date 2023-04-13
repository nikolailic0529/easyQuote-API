<?php

namespace App\Domain\Pipeline\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Database\Factories\PipelineStageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class PipelineStage.
 *
 * @property string|null   $pipeline_id
 * @property string|null   $stage_name
 * @property int|null      $stage_order
 * @property float|null    $stage_percentage
 * @property string        $qualified_stage_name
 * @property Pipeline|null $pipeline
 */
class PipelineStage extends Model
{
    use Uuid;
    use SoftDeletes;
    use HasFactory;

    protected $guarded = [];

    protected static function newFactory(): PipelineStageFactory
    {
        return PipelineStageFactory::new();
    }

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    public function getQualifiedStageNameAttribute(): string
    {
        return sprintf('%s. %s', $this->stage_order, $this->stage_name);
    }
}

<?php

namespace App\Models;

use App\Models\Pipeline\Pipeline;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $model_type
 * @property string|null $pipeline_id
 * @property string|null $latest_model_updated_at
 *
 * @property-read Pipeline|null $pipeline
 */
class PipelinerModelUpdateLog extends Model
{
    use Uuid, HasFactory;

    protected $guarded = [];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }
}

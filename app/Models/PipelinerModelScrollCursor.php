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
 * @property string|null $cursor Base64 encoded cursor
 *
 * @property-read Pipeline $pipeline
 */
class PipelinerModelScrollCursor extends Model
{
    use Uuid, HasFactory;

    protected $guarded = [];

    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }
}

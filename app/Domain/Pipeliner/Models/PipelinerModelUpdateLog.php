<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string|null $model_type
 * @property string|null $pipeline_id
 * @property string|null $latest_model_updated_at
 */
class PipelinerModelUpdateLog extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [];

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\SalesUnit\Models\SalesUnit::class);
    }
}

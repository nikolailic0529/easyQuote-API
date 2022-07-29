<?php

namespace App\Models;

use App\Traits\Uuid;
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
    use Uuid, HasFactory;

    protected $guarded = [];

    public function salesUnit(): BelongsTo
    {
        return $this->belongsTo(SalesUnit::class);
    }
}

<?php

namespace App\Models\Pipeliner;

use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string|null $strategy_name
 */
class PipelinerSyncStrategyLog extends Model
{
    use Uuid, HasFactory;

    protected $guarded = [];

    public function model(): MorphTo
    {
        return $this->morphTo('model');
    }
}

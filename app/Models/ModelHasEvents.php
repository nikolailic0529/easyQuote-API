<?php

namespace App\Models;

use App\Models\System\AppEvent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelHasEvents extends MorphPivot
{
    protected $table = 'model_has_events';

    public function event(): BelongsTo
    {
        return $this->belongsTo(AppEvent::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('model');
    }
}

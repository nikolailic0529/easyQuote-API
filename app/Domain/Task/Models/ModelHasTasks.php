<?php

namespace App\Domain\Task\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ModelHasTasks extends MorphPivot
{
    protected $table = 'model_has_tasks';

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('model');
    }
}

<?php

namespace App\Models;

use App\Models\Task\Task;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

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

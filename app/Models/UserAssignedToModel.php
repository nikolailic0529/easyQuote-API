<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property CarbonInterface|null $assignment_start_date
 * @property CarbonInterface|null $assignment_end_date
 */
class UserAssignedToModel extends MorphPivot
{
    protected $casts = [
        'assignment_start_date' => 'date',
        'assignment_end_date' => 'date'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('model');
    }
}

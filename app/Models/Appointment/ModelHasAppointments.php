<?php

namespace App\Models\Appointment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property Model|null $related
 */
class ModelHasAppointments extends MorphPivot
{
    protected $table = 'model_has_appointments';

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('model');
    }
}

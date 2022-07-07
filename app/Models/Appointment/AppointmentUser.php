<?php

namespace App\Models\Appointment;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentUser extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

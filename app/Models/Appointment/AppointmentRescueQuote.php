<?php

namespace App\Models\Appointment;

use App\Models\Quote\Quote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentRescueQuote extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }
}

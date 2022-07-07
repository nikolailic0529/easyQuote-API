<?php

namespace App\Models\Appointment;

use App\Models\Quote\Quote;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentWorldwideQuote extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuote::class, 'quote_id');
    }
}

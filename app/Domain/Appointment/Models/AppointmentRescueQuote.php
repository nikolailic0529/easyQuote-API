<?php

namespace App\Domain\Appointment\Models;

use App\Domain\Rescue\Models\Quote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentRescueQuote extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'quote_id');
    }
}

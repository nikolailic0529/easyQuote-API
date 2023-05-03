<?php

namespace App\Domain\Appointment\Models;

use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentWorldwideQuote extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(WorldwideQuote::class, 'quote_id');
    }
}

<?php

namespace App\Domain\Appointment\Models;

use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentOpportunity extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }
}

<?php

namespace App\Models\Appointment;

use App\Models\Contact;
use App\Models\Opportunity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentOpportunity extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class, 'opportunity_id');
    }
}

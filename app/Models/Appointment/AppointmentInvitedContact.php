<?php

namespace App\Models\Appointment;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentInvitedContact extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}

<?php

namespace App\Domain\Appointment\Models;

use App\Domain\Contact\Models\Contact;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentInvitedContact extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}

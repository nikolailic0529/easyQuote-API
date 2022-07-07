<?php

namespace App\Models\Appointment;

use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AppointmentCompany extends Pivot
{
    public function related(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}

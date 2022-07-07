<?php

namespace App\Models\Appointment;

use App\Models\Attachment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

class AppointmentAttachment extends MorphPivot
{
    protected $table = 'attachables';

    public function related(): BelongsTo
    {
        return $this->belongsTo(Attachment::class, 'attachment_id');
    }
}

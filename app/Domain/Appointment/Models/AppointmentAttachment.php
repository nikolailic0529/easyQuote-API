<?php

namespace App\Domain\Appointment\Models;

use App\Domain\Attachment\Models\Attachment;
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

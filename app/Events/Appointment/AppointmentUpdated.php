<?php

namespace App\Events\Appointment;

use App\Models\Appointment\Appointment;
use Illuminate\Queue\SerializesModels;

final class AppointmentUpdated
{
    use SerializesModels;

    public function __construct(public readonly Appointment $appointment,
                                public readonly Appointment $oldAppointment)
    {
    }
}

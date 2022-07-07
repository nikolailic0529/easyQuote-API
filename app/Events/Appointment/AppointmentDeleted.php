<?php

namespace App\Events\Appointment;

use App\Models\Appointment\Appointment;
use Illuminate\Queue\SerializesModels;

final class AppointmentDeleted
{
    use SerializesModels;

    public function __construct(public readonly Appointment $appointment)
    {
    }
}

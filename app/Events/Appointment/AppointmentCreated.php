<?php

namespace App\Events\Appointment;

use App\Models\Appointment\Appointment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

final class AppointmentCreated
{
    use SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly ?Model $causer,
    )
    {
    }
}

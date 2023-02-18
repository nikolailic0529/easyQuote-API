<?php

namespace App\Domain\Appointment\Events;

use App\Domain\Appointment\Models\Appointment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Queue\SerializesModels;

final class AppointmentDeleted
{
    use SerializesModels;

    public function __construct(
        public readonly Appointment $appointment,
        public readonly ?Model $causer,
    ) {
    }
}

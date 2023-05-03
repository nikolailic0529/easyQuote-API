<?php

namespace App\Domain\Appointment\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface HasOwnAppointments
{
    public function ownAppointments(): MorphToMany;
}

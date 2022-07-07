<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface LinkedToAppointments
{
    public function appointments(): BelongsToMany;
}
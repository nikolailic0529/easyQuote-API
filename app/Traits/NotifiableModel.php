<?php

namespace App\Traits;

use App\Models\ModelNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait NotifiableModel
{
    public function notifications(): MorphMany
    {
        return $this->morphMany(ModelNotification::class, 'model');
    }
}

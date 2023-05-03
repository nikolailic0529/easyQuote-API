<?php

namespace App\Domain\Notification\Concerns;

use App\Domain\Notification\Models\ModelNotification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait NotifiableModel
{
    public function notifications(): MorphMany
    {
        return $this->morphMany(ModelNotification::class, 'model');
    }
}

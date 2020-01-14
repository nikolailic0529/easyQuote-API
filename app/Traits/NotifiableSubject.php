<?php

namespace App\Traits;

use App\Models\System\Notification;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait NotifiableSubject
{
    public function notifications(): MorphMany
    {
        return $this->morphMany(Notification::class, 'subject')->withTrashed();
    }
}

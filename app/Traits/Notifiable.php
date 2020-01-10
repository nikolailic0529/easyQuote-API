<?php

namespace App\Traits;

use App\Models\System\Notification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\RoutesNotifications;

trait Notifiable
{
    use RoutesNotifications;

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }
}

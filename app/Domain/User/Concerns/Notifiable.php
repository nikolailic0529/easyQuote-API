<?php

namespace App\Domain\User\Concerns;

use App\Domain\Notification\Models\Notification;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\RoutesNotifications;

trait Notifiable
{
    use RoutesNotifications;

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function setRecentNotificationsLimitAttribute($value): void
    {
        $this->attributes['recent_notifications_limit'] = (int) $value;
    }
}

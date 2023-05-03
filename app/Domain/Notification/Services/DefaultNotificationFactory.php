<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Contracts\NotificationFactory;
use App\Domain\Notification\Models\Notification;
use App\Domain\Priority\Enum\Priority;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

class DefaultNotificationFactory implements NotificationFactory
{
    public function subject(Model $model): PendingNotification
    {
        return $this->newPendingNotification()->subject($model);
    }

    public function message(string $message): PendingNotification
    {
        return $this->newPendingNotification()->message($message);
    }

    public function for(User $user): PendingNotification
    {
        return $this->newPendingNotification()->for($user);
    }

    public function url(?string $url): PendingNotification
    {
        return $this->newPendingNotification()->url($url);
    }

    public function priority(Priority|int $priority): PendingNotification
    {
        return $this->newPendingNotification()->priority($priority);
    }

    protected function newPendingNotification(): PendingNotification
    {
        return new PendingNotification($this, new Notification());
    }
}

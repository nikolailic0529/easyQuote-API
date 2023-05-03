<?php

namespace App\Domain\Notification\Contracts;

use App\Domain\Notification\Services\PendingNotification;
use App\Domain\Priority\Enum\Priority;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;

interface NotificationFactory
{
    /**
     * Set notification subject.
     */
    public function subject(Model $model): PendingNotification;

    /**
     * Set notification message.
     */
    public function message(string $message): PendingNotification;

    /**
     * Set notifiable user.
     */
    public function for(User $user): PendingNotification;

    /**
     * Set notification url.
     *
     * @param ?string $url
     */
    public function url(?string $url): PendingNotification;

    /**
     * Set notification priority.
     */
    public function priority(Priority|int $priority): PendingNotification;
}

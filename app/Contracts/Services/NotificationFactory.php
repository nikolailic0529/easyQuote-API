<?php

namespace App\Contracts\Services;

use App\Enum\Priority;
use App\Models\User;
use App\Services\Notification\Models\PendingNotification;
use Illuminate\Database\Eloquent\Model;

interface NotificationFactory
{
    /**
     * Set notification subject.
     *
     * @param Model $model
     * @return PendingNotification
     */
    public function subject(Model $model): PendingNotification;

    /**
     * Set notification message.
     *
     * @param string $message
     * @return PendingNotification
     */
    public function message(string $message): PendingNotification;

    /**
     * Set notifiable user.
     *
     * @param User $user
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
     *
     * @param Priority|int $priority
     * @return PendingNotification
     */
    public function priority(Priority|int $priority): PendingNotification;
}

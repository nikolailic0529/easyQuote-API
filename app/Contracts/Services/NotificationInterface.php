<?php

namespace App\Contracts\Services;

use App\Models\System\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

interface NotificationInterface
{
    /**
     * Set attributes for Notification instance.
     *
     * @param array $attributes
     * @return \App\Contracts\Services\NotificationInterface
     */
    public function setAttributes(array $attributes = []): NotificationInterface;

    /**
     * Set a specified attribute value for Notification instance.
     *
     * @param string $attribute
     * @param mixed $value
     * @return \App\Contracts\Services\NotificationInterface
     */
    public function setAttribute(string $attribute, $value): NotificationInterface;

    /**
     * Retrieve a specified Notification attribute value.
     *
     * @param string $attribute
     * @return mixed
     */
    public function getAttribute(string $attribute);

    /**
     * Set a specified notifiable subject.
     *
     * @param \Illuminate\Database\Eloquent\Model $model
     * @return \App\Contracts\Services\NotificationInterface
     */
    public function subject(Model $model): NotificationInterface;

    /**
     * Set a specified Message.
     *
     * @param string $message
     * @return \App\Contracts\Services\NotificationInterface
     */
    public function message(string $message): NotificationInterface;

    /**
     * Set a notifiable User.
     *
     * @param \App\Models\User $user
     * @return \App\Contracts\Services\NotificationInterface
     */
    public function for(User $user): NotificationInterface;

    /**
     * Set a specified Url.
     *
     * @param string $url
     * @return \App\Contracts\Services\NotificationInterface
     */
    public function url(string $url): NotificationInterface;

    /**
     * Set a specified priority.
     *
     * @param integer $priority
     * @return \App\Contracts\Services\NotificationInterface
     */
    public function priority(int $priority): NotificationInterface;

    /**
     * Store the Notification.
     *
     * @return \App\Models\System\Notification
     */
    public function store(): Notification;

    /**
     * Dispatch notification storing in queue.
     *
     * @return void
     */
    public function queue(): void;
}

<?php

namespace App\Services;

use App\Contracts\Services\NotificationInterface;
use App\Models\System\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class NotificationStorage implements NotificationInterface
{
    /** @var \App\Models\System\Notification */
    protected $notification;

    protected static $fallbackRoute = UN_FALLBACK_ROUTE;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function __isset($attribute)
    {
        return isset($this->notification[$attribute]);
    }

    public function getAttribute(string $attribute)
    {
        return $this->notification->getAttribute($attribute);
    }

    public function setAttributes(array $attributes = []): NotificationInterface
    {
        $this->notification->setRawAttributes($attributes);

        return $this;
    }

    public function setAttribute(string $attribute, $value): NotificationInterface
    {
        $this->notification->setAttribute($attribute, $value);

        return $this;
    }

    public function subject(Model $model): NotificationInterface
    {
        $this->notification->subject()->associate($model);

        return $this;
    }

    public function message(string $value): NotificationInterface
    {
        return $this->setAttribute(__FUNCTION__, $value);
    }

    public function for(User $user): NotificationInterface
    {
        $this->notification->user()->associate($user);

        return $this;
    }

    public function url(string $value): NotificationInterface
    {
        return $this->setAttribute(__FUNCTION__, $value);
    }

    public function priority(int $value): NotificationInterface
    {
        return $this->setAttribute(__FUNCTION__, $value);
    }

    public function store(): Notification
    {
        $this->checkAttributes();

        return tap($this->notification)->save();
    }

    protected function checkAttributes(): void
    {
        if (!isset($this->notification['url'])) {
            $this->url(ui_route(static::$fallbackRoute));
        }
    }
}

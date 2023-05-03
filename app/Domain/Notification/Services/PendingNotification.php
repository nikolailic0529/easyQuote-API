<?php

namespace App\Domain\Notification\Services;

use App\Domain\Notification\Contracts\NotificationFactory;
use App\Domain\Notification\Jobs\CreateNotification;
use App\Domain\Notification\Models\Notification;
use App\Domain\Priority\Enum\Priority;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\Conditionable;

class PendingNotification
{
    use Conditionable;

    public function __construct(
        protected NotificationFactory $factory,
        protected Notification $notification
    ) {
    }

    public function queue(): Notification
    {
        $this->validateAttributes();

        return tap($this->notification, static function (Notification $notification): void {
            CreateNotification::dispatch($notification);
        });
    }

    public function queueIf(bool $value): Notification|null
    {
        if ($value) {
            return $this->queue();
        }

        return null;
    }

    public function push(): Notification
    {
        $this->validateAttributes();

        return tap($this->notification, static function (Notification $notification): void {
            $notification->saveOrFail();
        });
    }

    public function pushIf(bool $value): Notification|null
    {
        if ($value) {
            return $this->push();
        }

        return null;
    }

    public function subject(Model $model): PendingNotification
    {
        return tap($this, function () use ($model): void {
            $this->notification->subject()->associate($model);
        });
    }

    public function message(string $message): PendingNotification
    {
        return tap($this, function () use ($message): void {
            $this->notification->message = $message;
        });
    }

    public function for(User $user): PendingNotification
    {
        return tap($this, function () use ($user): void {
            $this->notification->user()->associate($user);
        });
    }

    public function url(string|null $url): PendingNotification
    {
        return tap($this, function () use ($url): void {
            $this->notification->url = $url;
        });
    }

    public function priority(Priority|int $priority): PendingNotification
    {
        return tap($this, function () use ($priority): void {
            $this->notification->priority = $priority instanceof Priority ? $priority->value : $priority;
        });
    }

    protected function validateAttributes(): void
    {
        $this->notification->updateTimestamps();

        if (false === isset($this->notification['url'])) {
            $this->url(ui_route(UN_FALLBACK_ROUTE));
        }
    }
}

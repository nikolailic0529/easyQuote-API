<?php

namespace App\Domain\Notification\Channels;

use Illuminate\Notifications\Notification;

final class CustomDatabaseChannel
{
    public function send(mixed $notifiable, Notification $notification)
    {
        return $notifiable->routeNotificationFor('database', $notification)->create(
            $this->getData($notifiable, $notification)
        );
    }

    private function getData(mixed $notifiable, Notification $notification): array
    {
        if (method_exists($notification, 'toDatabase')) {
            return is_array($data = $notification->toDatabase($notifiable))
                ? $data : $data->data;
        }

        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        throw new \RuntimeException('Notification is missing toDatabase / toArray method.');
    }
}

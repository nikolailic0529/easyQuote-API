<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait OptionalAction
{
    protected function optionalAction(MailMessage $message, $text, $url): MailMessage
    {
        return tap($message, function (MailMessage $message) use ($text, $url) {
            if (isset($url)) {
                $message->action($text, $url);
            }
        });
    }
}

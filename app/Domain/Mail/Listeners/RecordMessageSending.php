<?php

namespace App\Domain\Mail\Listeners;

use App\Domain\Mail\Models\MailLog;
use App\Foundation\Support\Elasticsearch\Jobs\IndexSearchableEntity;
use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Mail\Events\MessageSending;

final class RecordMessageSending
{
    public function __construct(
        private readonly BusDispatcher $busDispatcher,
    ) {
    }

    public function handle(MessageSending $event): void
    {
        rescue(function () use ($event): void {
            $log = new MailLog();

            $log->message_id = $event->message->getId();
            $log->cc = $event->message->getCc();
            $log->bcc = $event->message->getBcc();
            $log->reply_to = $event->message->getReplyTo();
            $log->from = $event->message->getFrom();
            $log->to = $event->message->getTo();
            $log->subject = $event->message->getSubject();
            $log->body = $event->message->getBody();
            $log->sent_at = $event->message->getDate();

            $log->save();

            $this->busDispatcher->dispatch(
                new IndexSearchableEntity($log)
            );
        });
    }
}

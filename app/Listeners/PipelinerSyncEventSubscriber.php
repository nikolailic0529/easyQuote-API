<?php

namespace App\Listeners;

use App\Events\Pipeliner\QueuedPipelinerSyncLocalEntitySkipped;
use App\Events\Pipeliner\QueuedPipelinerSyncRemoteEntitySkipped;
use App\Models\User;
use Illuminate\Contracts\Events\Dispatcher;

class PipelinerSyncEventSubscriber
{
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(QueuedPipelinerSyncLocalEntitySkipped::class, [self::class, 'handleLocalEntitySkippedEvent']);
        $events->listen(QueuedPipelinerSyncRemoteEntitySkipped::class, [self::class, 'handleRemoteEntitySkippedEvent']);
    }

    public function handleLocalEntitySkippedEvent(QueuedPipelinerSyncLocalEntitySkipped $event): void
    {
        if ($event->causer instanceof User) {
            notification()
                ->for($event->causer)
                ->priority(3)
                ->url(ui_route('opportunity.update', ['opportunity' => $event->opportunity]))
                ->message("[Pipeliner sync]: local opportunity ({$event->opportunity->getKey()}) skipped due to errors.")
                ->push();
        }
    }

    public function handleRemoteEntitySkippedEvent(QueuedPipelinerSyncRemoteEntitySkipped $event): void
    {
        if ($event->causer instanceof User) {
            notification()
                ->for($event->causer)
                ->priority(3)
                ->message("[Pipeliner sync]: remote opportunity ({$event->opportunity->id}) skipped due to errors.")
                ->push();
        }
    }
}
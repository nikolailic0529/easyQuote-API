<?php

namespace App\Events\Pipeliner;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QueuedPipelinerSyncProcessed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        protected readonly ?Model $causer,
        protected int $totalEntities,
        protected int $pendingEntities
    ) {
        //
    }

    public function broadcastOn(): array
    {
        return [new Channel('pipeliner-sync')];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.processed';
    }

    public function broadcastWith(): array
    {
        return [
            'progress' => 100,
            'total_entities' => $this->totalEntities,
            'pending_entities' => $this->pendingEntities,
        ];
    }
}

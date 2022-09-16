<?php

namespace App\Events\Pipeliner;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QueuedPipelinerSyncProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        protected readonly int $totalEntities,
        protected readonly int $pendingEntities,
        protected readonly ?Model $causer,
        protected readonly string $correlationId
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('pipeliner-sync')];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.progress';
    }

    public function broadcastWith(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'progress' => $this->progress(),
            'total_entities' => $this->totalEntities,
            'pending_entities' => $this->pendingEntities,
        ];
    }

    private function progress(): int
    {
        return $this->totalEntities > 0 ? round(($this->processedEntities() / $this->totalEntities) * 100) : 0;
    }

    private function processedEntities(): int
    {
        return $this->totalEntities - $this->pendingEntities;
    }
}

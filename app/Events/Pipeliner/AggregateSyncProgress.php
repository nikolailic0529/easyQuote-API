<?php

namespace App\Events\Pipeliner;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AggregateSyncProgress implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        protected readonly int $total,
        protected readonly int $pending,
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
            'progress' => $this->progress(),
            'total_entities' => $this->total,
            'pending_entities' => $this->pending,
        ];
    }

    private function progress(): int
    {
        return $this->total > 0 ? round(($this->processedEntities() / $this->total) * 100) : 0;
    }

    private function processedEntities(): int
    {
        return $this->total - $this->pending;
    }
}

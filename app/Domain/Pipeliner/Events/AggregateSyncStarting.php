<?php

namespace App\Domain\Pipeliner\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * @property array<string, int>
 */
final class AggregateSyncStarting implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly string $aggregateId,
        public readonly int $total,
        public readonly int $pending,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('pipeliner-sync')];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.starting';
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

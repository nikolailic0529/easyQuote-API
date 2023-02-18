<?php

namespace App\Domain\Pipeliner\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AggregateSyncCompleted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public \DateTimeImmutable $occurrence;

    public function __construct(
        public readonly string $aggregateId,
        public readonly int $total,
    ) {
        $this->occurrence = now()->toDateTimeImmutable();
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
            'total_entities' => $this->total,
            'pending_entities' => 0,
        ];
    }
}

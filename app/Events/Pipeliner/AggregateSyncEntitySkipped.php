<?php

namespace App\Events\Pipeliner;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class AggregateSyncEntitySkipped implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public \DateTimeImmutable $occurrence;

    public function __construct(
        public readonly string $aggregateId,
        public readonly object $entity,
        public readonly string $strategy,
        public readonly ?Model $causer,
        public readonly ?Throwable $e,
    ) {
        $this->occurrence = now()->toDateTimeImmutable();
    }

    public function broadcastOn(): array
    {
        return [new Channel('pipeliner-sync')];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.entity-skipped';
    }

    public function broadcastWith(): array
    {
        return [
            'entity_id' => $this->entity->id,
            'entity_type' => class_basename($this->entity),
        ];
    }
}

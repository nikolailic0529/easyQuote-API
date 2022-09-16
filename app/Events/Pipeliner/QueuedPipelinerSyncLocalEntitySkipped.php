<?php

namespace App\Events\Pipeliner;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class QueuedPipelinerSyncLocalEntitySkipped implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Model $model,
        public readonly ?Model $causer,
        public readonly string $correlationId,
        public readonly ?Throwable $e,
    ) {
    }

    public function broadcastOn(): array
    {
        return [new Channel('pipeliner-sync')];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.local-entity-skipped';
    }

    public function broadcastWith(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'model_id' => $this->model->getKey(),
        ];
    }
}

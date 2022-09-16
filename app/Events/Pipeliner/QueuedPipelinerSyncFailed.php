<?php

namespace App\Events\Pipeliner;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

final class QueuedPipelinerSyncFailed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        protected readonly Throwable $exception,
        protected readonly ?Model $causer
    ) {
        //
    }

    public function broadcastOn(): array
    {
        return [new Channel('pipeliner-sync')];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.failed';
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }
}

<?php

namespace App\Events\Pipeliner;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QueuedPipelinerSyncFailed implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(protected readonly \Throwable $exception,
                                protected readonly ?Model $causer)
    {
        //
    }

    public function broadcastOn(): array
    {
        if (!$this->causer instanceof User) {
            return [];
        }

        return [new PrivateChannel('user.'.$this->causer->getKey())];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.failed';
    }

    /**
     * @return \Throwable
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }
}

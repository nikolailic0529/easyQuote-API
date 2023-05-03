<?php

namespace App\Domain\Pipeliner\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AggregateSyncFailed implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public readonly \DateTimeImmutable $occurrence;

    public function __construct(
        public readonly string $aggregateId,
        public readonly \Throwable $exception,
    ) {
        $this->occurrence = now()->toDateTimeImmutable();
    }

    public function broadcastOn(): array
    {
        return [new Channel('pipeliner-sync')];
    }

    public function broadcastAs(): string
    {
        return 'queued-pipeliner-sync.failed';
    }

    public function getException(): \Throwable
    {
        return $this->exception;
    }
}

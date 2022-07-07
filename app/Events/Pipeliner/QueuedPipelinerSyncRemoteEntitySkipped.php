<?php

namespace App\Events\Pipeliner;

use App\Integrations\Pipeliner\Models\OpportunityEntity;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class QueuedPipelinerSyncRemoteEntitySkipped implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly OpportunityEntity $opportunity,
                                public readonly ?Model $causer,
                                public readonly string $correlationId)
    {
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
        return 'queued-pipeliner-sync.remote-entity-skipped';
    }

    public function broadcastWith(): array
    {
        return [
            'correlation_id' => $this->correlationId,
            'opportunity_id' => $this->opportunity->id,
        ];
    }
}

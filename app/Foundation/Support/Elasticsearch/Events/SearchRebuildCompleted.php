<?php

namespace App\Foundation\Support\Elasticsearch\Events;

use App\Domain\User\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Database\Eloquent\Model;

final class SearchRebuildCompleted implements ShouldBroadcast
{
    public function __construct(
        protected readonly ?Model $causer,
    ) {
    }

    public function broadcastOn(): array
    {
        if ($this->causer instanceof User) {
            return [new PrivateChannel('user.'.$this->causer->getKey())];
        }

        return [];
    }

    public function broadcastAs(): string
    {
        return 'search-rebuild.completed';
    }
}

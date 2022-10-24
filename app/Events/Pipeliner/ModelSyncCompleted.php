<?php

namespace App\Events\Pipeliner;

use App\Contracts\ProvidesIdForHumans;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;

final class ModelSyncCompleted implements ShouldBroadcastNow
{
    public function __construct(
        public readonly Model $model,
        public readonly ?Model $causer,
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
        return 'model_sync.completed';
    }

    public function broadcastWith(): array
    {
        $modelName = class_basename($this->model);
        $modelIdForHumans = $this->model instanceof ProvidesIdForHumans
            ? $this->model->getIdForHumans()
            : $this->model->getKey();

        return [
            'message' => "Data sync of $modelName [$modelIdForHumans] has been completed."
        ];
    }
}
<?php

namespace App\Domain\Pipeliner\Events;

use App\Domain\Eloquent\Contracts\ProvidesIdForHumans;
use App\Domain\User\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Database\Eloquent\Model;

final class ModelSyncCompleted implements ShouldBroadcastNow
{
    public readonly \DateTimeImmutable $occurrenceTime;

    public function __construct(
        public readonly Model $model,
        public readonly ?Model $causer,
    ) {
        $this->occurrenceTime = now()->toDateTimeImmutable();
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
            'message' => "Data sync of $modelName [$modelIdForHumans] has been completed.",
        ];
    }
}

<?php

namespace App\Domain\Pipeliner\Events;

use Illuminate\Database\Eloquent\Model;

final class SyncStrategyPerformed
{
    public readonly Model $model;
    public readonly \DateTimeImmutable $occurrence;

    public function __construct(
        Model $model,
        public readonly string $strategyClass,
        public readonly ?string $aggregateId,
    ) {
        $this->model = $model->withoutRelations();
        $this->occurrence = now()->toDateTimeImmutable();
    }
}

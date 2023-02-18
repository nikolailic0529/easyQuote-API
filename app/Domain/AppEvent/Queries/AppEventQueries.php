<?php

namespace App\Domain\AppEvent\Queries;

use App\Domain\AppEvent\Models\AppEvent;
use Illuminate\Database\Eloquent\Builder;

class AppEventQueries
{
    public function appEventWithPayloadOfQuery(string $name): Builder
    {
        return AppEvent::query()
            ->where('name', 'pipeliner-aggregate-sync-completed')
            ->latest('occurred_at')
            ->select(['*', 'payload']);
    }
}

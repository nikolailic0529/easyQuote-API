<?php

namespace App\Queries;

use App\Models\System\AppEvent;
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
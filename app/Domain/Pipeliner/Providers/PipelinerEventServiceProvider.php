<?php

namespace App\Domain\Pipeliner\Providers;

use App\Domain\CustomField\Events\CustomFieldValuesUpdated;
use App\Domain\Pipeliner\Listeners\FlushPipelinerModelScrollCursorsOnUnitsUpdate;
use App\Domain\Pipeliner\Listeners\PipelinerSyncEventSubscriber;
use App\Domain\Pipeliner\Listeners\SyncCustomFieldValuesInPipeliner;
use App\Domain\SalesUnit\Events\SalesUnitsUpdated;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class PipelinerEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        PipelinerSyncEventSubscriber::class,
    ];

    protected $listen = [
        SalesUnitsUpdated::class => [
            FlushPipelinerModelScrollCursorsOnUnitsUpdate::class,
        ],
        CustomFieldValuesUpdated::class => [
            SyncCustomFieldValuesInPipeliner::class,
        ],
    ];
}

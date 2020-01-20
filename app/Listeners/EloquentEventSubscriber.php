<?php

namespace App\Listeners;

use Illuminate\Database\Eloquent\Model;
use Str;

class EloquentEventSubscriber
{
    /**
     * Register the listeners for the subscriber.
     *
     * @param  \Illuminate\Events\Dispatcher  $events
     */
    public function subscribe($events)
    {
        $events->listen(
            [
                'eloquent.created: *',
                'eloquent.deleted: *',
                'eloquent.submitted: *',
                'eloquent.unsubmitted: *'
            ],
            self::class . '@flushModelCache'
        );
    }

    public function flushModelCache($event): void
    {
        if (!is_string($event)) {
            return;
        }

        $model = $this->resolveModel($event);
        $table = $model->getTable();

        cache()->tags($table.TABLE_COUNT_POSTFIX)->flush();
    }

    private function resolveModel(string $event): Model
    {
        return app(Str::after($event, ': '));
    }
}

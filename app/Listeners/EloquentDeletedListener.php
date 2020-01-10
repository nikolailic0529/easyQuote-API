<?php

namespace App\Listeners;

use Str;

class EloquentDeletedListener
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        if (!is_string($event)) {
            return;
        }

        try {
            $class = Str::after($event, ': ');
            $model = app($class);

            cache()->tags($model->getTable())->flush();
        } catch (\Throwable $exception) {
            report_logger(['error' => FFTC_01], $exception->getMessage());
        }
    }
}

<?php

namespace App\Domain\Pipeliner\Listeners;

use App\Domain\Pipeliner\Models\PipelinerModelScrollCursor;
use App\Domain\SalesUnit\Events\SalesUnitsUpdated;
use App\Domain\SalesUnit\Models\SalesUnit;

class FlushPipelinerModelScrollCursorsOnUnitsUpdate
{
    public function handle(SalesUnitsUpdated $event): void
    {
        if ($this->enabledUnitsWereChanged($event)) {
            PipelinerModelScrollCursor::query()->delete();
        }
    }

    protected function enabledUnitsWereChanged(SalesUnitsUpdated $event): bool
    {
        $oldEnabled = $event->old->filter(static function (SalesUnit $unit): bool {
            return $unit->is_enabled;
        });

        $newEnabled = $event->new->filter(static function (SalesUnit $unit): bool {
            return $unit->is_enabled;
        });

        if ($oldEnabled->count() !== $newEnabled->count()) {
            return true;
        }

        return $oldEnabled->pluck('unit_name')
            ->diff($newEnabled->pluck('unit_name'))
            ->isNotEmpty();
    }
}

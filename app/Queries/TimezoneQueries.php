<?php

namespace App\Queries;

use App\Models\Data\Timezone;
use Illuminate\Database\Eloquent\Builder;

class TimezoneQueries
{
    public function listOfTimezonesQuery(): Builder
    {
        $model = new Timezone();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->qualifyColumn('text'),
                $model->qualifyColumn('value'),
            ]);
    }
}
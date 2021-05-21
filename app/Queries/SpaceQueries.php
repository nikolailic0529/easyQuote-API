<?php

namespace App\Queries;

use App\Models\Space;
use Illuminate\Database\Eloquent\Builder;

class SpaceQueries
{
    public function listOfSpacesQuery(): Builder
    {
        $spaceModel = new Space();

        return $spaceModel->newQuery()
            ->select([
                $spaceModel->getQualifiedKeyName(),
                $spaceModel->qualifyColumn('space_name')
            ]);

    }
}

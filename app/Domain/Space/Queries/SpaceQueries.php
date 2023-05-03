<?php

namespace App\Domain\Space\Queries;

use App\Domain\Space\Models\Space;
use Illuminate\Database\Eloquent\Builder;

class SpaceQueries
{
    public function listOfSpacesQuery(): Builder
    {
        $spaceModel = new Space();

        return $spaceModel->newQuery()
            ->select([
                $spaceModel->getQualifiedKeyName(),
                $spaceModel->qualifyColumn('space_name'),
            ]);
    }
}

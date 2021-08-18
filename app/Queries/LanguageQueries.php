<?php

namespace App\Queries;

use App\Models\Data\Language;
use Illuminate\Database\Eloquent\Builder;

class LanguageQueries
{
    public function listOfLanguagesQuery(): Builder
    {
        $model = new Language();

        return $model->newQuery()
            ->orderBy($model->qualifyColumn('name'));
    }
}
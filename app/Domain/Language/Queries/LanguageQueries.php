<?php

namespace App\Domain\Language\Queries;

use App\Domain\Language\Models\Language;
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

<?php

namespace App\Domain\Language\Queries;

use App\Domain\Language\Models\Language;
use Illuminate\Database\Eloquent\Builder;

class LanguageQueries
{
    public function listLanguagesQuery(): Builder
    {
        $model = new Language();

        return $model->newQuery()
            ->orderBy($model->qualifyColumn('name'));
    }

    public function listContactLanguagesQuery(): Builder
    {
        $model = new Language();

        return $model->newQuery()
            ->select([$model->qualifyColumn('*')])
            ->join('contact_languages', 'contact_languages.language_id', $model->getQualifiedKeyName())
            ->orderBy('contact_languages.entity_order');
    }
}

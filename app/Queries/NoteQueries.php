<?php

namespace App\Queries;

use App\Contracts\HasOwner;
use App\Contracts\HasOwnNotes;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NoteQueries
{
    public function listNotesOfModelQuery(HasOwnNotes $model, Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var Model&HasOwner $noteModel */
        $noteModel = $model->notes()->getRelated();

        $ownerModel = $noteModel->owner()->getModel();

        $query = $model->notes()->getQuery()
            ->with('owner:'.collect([$ownerModel->getKeyName(), 'first_name', 'last_name', 'user_fullname'])->join(','))
            ->select([
                $noteModel->getQualifiedKeyName(),
                ...$noteModel->qualifyColumns([
                    $noteModel->owner()->getQualifiedForeignKeyName(),
                    'note',
                    'created_at',
                    'updated_at',
                ]),
            ]);

        return RequestQueryBuilder::for($query, $request)
            ->allowQuickSearchFields('note')
            ->allowOrderFields('created_at')
            ->qualifyOrderFields(created_at: $noteModel->getQualifiedCreatedAtColumn())
            ->enforceOrderBy($noteModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
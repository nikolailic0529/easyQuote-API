<?php

namespace App\Domain\Note\Queries;

use App\Domain\Note\Contracts\HasOwnNotes;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class NoteQueries
{
    public function listNotesOfModelQuery(HasOwnNotes $model, Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var Model&\App\Domain\User\Contracts\HasOwner $noteModel */
        $noteModel = $model->notes()->getRelated();

        $ownerModel = $noteModel->owner()->getModel();

        $query = $model->notes()->getQuery()
            ->with('owner:'.collect([$ownerModel->getKeyName(), 'first_name', 'last_name', 'user_fullname'])->join(','))
            ->select([
                $noteModel->getQualifiedKeyName(),
                ...$noteModel->qualifyColumns([
                    $noteModel->owner()->getQualifiedForeignKeyName(),
                    'note',
                    'flags',
                    $noteModel->getCreatedAtColumn(),
                    $noteModel->getUpdatedAtColumn(),
                ]),
            ]);

        return RequestQueryBuilder::for($query, $request)
            ->allowQuickSearchFields('note')
            ->allowOrderFields('note', 'text', 'created_at')
            ->qualifyOrderFields(
                created_at: $noteModel->getQualifiedCreatedAtColumn(),
                text: $noteModel->qualifyColumn('note'),
            )
            ->enforceOrderBy($noteModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}

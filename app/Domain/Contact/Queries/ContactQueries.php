<?php

namespace App\Domain\Contact\Queries;

use App\Domain\Authorization\Queries\Scopes\CurrentUserScope;
use App\Domain\Contact\Models\Contact;
use App\Domain\User\Models\User;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class ContactQueries
{
    public function __construct(protected Elasticsearch $elasticsearch,
                                protected Gate $gate)
    {
    }

    public function listOfContactsQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var User $user */
        $user = $request->user() ?? new User();

        $contactModel = new Contact();
        $userModel = new User();

        $query = $contactModel
            ->newQuery()
            ->select([
                $contactModel->qualifyColumn('*'),
            ])
            ->with([
                'user' => static function (Relation $relation): void {
                    $model = new User();

                    $relation->select([
                        $model->getQualifiedKeyName(),
                        ...$model->qualifyColumns([
                            'first_name',
                            'middle_name',
                            'last_name',
                            'user_fullname',
                            'picture_id',
                        ]),
                    ]);
                },
            ])
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(),
                $contactModel->user()->getQualifiedForeignKeyName())
            ->tap(CurrentUserScope::from($request, $this->gate));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch),
            )
            ->allowOrderFields(
                'created_at',
                'email',
                'first_name',
                'last_name',
                'is_verified',
                'job_title',
                'mobile',
                'phone',
                'user_fullname'
            )
            ->qualifyOrderFields(
                created_at: $contactModel->qualifyColumn('created_at'),
                email: $contactModel->qualifyColumn('email'),
                first_name: $contactModel->qualifyColumn('first_name'),
                last_name: $contactModel->qualifyColumn('last_name'),
                is_verified: $contactModel->qualifyColumn('is_verified'),
                job_title: $contactModel->qualifyColumn('job_title'),
                mobile: $contactModel->qualifyColumn('mobile'),
                phone: $contactModel->qualifyColumn('phone'),
                user_fullname: $userModel->qualifyColumn('user_fullname'),
            )
            ->enforceOrderBy($contactModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}

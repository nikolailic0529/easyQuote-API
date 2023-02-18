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

        /** @var \App\Domain\User\Models\User $user */
        $user = $request->user() ?? new User();

        $contactModel = new Contact();

        $query = $contactModel
            ->newQuery()
            ->tap(CurrentUserScope::from($request, $this->gate));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch),
            )
            ->allowOrderFields(...[
                'created_at',
                'email',
                'first_name',
                'last_name',
                'is_verified',
                'job_title',
                'mobile',
                'phone',
            ])
            ->enforceOrderBy($contactModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}

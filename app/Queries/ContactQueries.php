<?php

namespace App\Queries;

use App\Models\Address;
use App\Models\Contact;
use App\Models\User;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ContactQueries
{
    public function __construct(protected Elasticsearch $elasticsearch,
                                protected Gate          $gate)
    {
    }

    public function listOfContactsQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var User $user */
        $user = $request->user() ?? new User();

        $contactModel = new Contact();

        $query = $contactModel
            ->newQuery()
            ->when($this->gate->denies('viewAnyOwnerEntities', Address::class), function (Builder $builder) use ($user) {
                $builder->whereBelongsTo($user);
            });

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
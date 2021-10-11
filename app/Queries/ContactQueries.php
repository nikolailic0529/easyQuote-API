<?php

namespace App\Queries;

use App\Models\Contact;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ContactQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function listOfContactsQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $contactModel = new Contact();

        $query = $contactModel->newQuery();

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
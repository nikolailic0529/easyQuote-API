<?php

namespace App\Queries;

use App\Models\Address;
use App\Models\Data\Country;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AddressQueries
{
    public function __construct(protected Elasticsearch $elasticsearch)
    {
    }

    public function listOfAddressesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $addressModel = new Address();
        $countryModel = new Country();

        $query = $addressModel->newQuery()
            ->with('country')
            ->leftJoin($countryModel->getTable(), $countryModel->getQualifiedKeyName(), $addressModel->country()->getQualifiedForeignKeyName())
            ->select($addressModel->qualifyColumn('*'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch),
            )
            ->allowOrderFields(...[
                'created_at',
                'country',
                'address_type',
                'city',
                'post_code',
                'state',
                'street_address',
            ])
            ->qualifyOrderFields(
                created_at: $addressModel->qualifyColumn('created_at'),
                country: $countryModel->qualifyColumn('name'),
                address_type: $addressModel->qualifyColumn('address_type'),
                city: $addressModel->qualifyColumn('city'),
                post_code: $addressModel->qualifyColumn('post_code'),
                state: $addressModel->qualifyColumn('state'),
                street_address: $addressModel->qualifyColumn('address_1'),
            )
            ->enforceOrderBy($addressModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}
<?php

namespace App\Domain\Address\Queries;

use App\Domain\Address\Models\Address;
use App\Domain\Country\Models\Country;
use App\Domain\User\Models\User;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class AddressQueries
{
    public function __construct(protected Elasticsearch $elasticsearch,
                                protected Gate $gate)
    {
    }

    public function listOfAddressesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var User $user */
        $user = $request->user() ?? new User();

        $addressModel = new Address();
        $countryModel = new Country();
        $userModel = new User();

        $query = $addressModel->newQuery()
            ->select([
                $addressModel->qualifyColumn('*'),
            ])
            ->with([
                'country' => static function (Relation $relation): void {
                    $model = new Country();
                    $relation->select([
                        $model->getQualifiedKeyName(),
                        $model->qualifyColumn('iso_3166_2'),
                        $model->qualifyColumn('name'),
                    ]);
                },
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
            ->leftJoin($countryModel->getTable(), $countryModel->getQualifiedKeyName(),
                $addressModel->country()->getQualifiedForeignKeyName())
            ->leftJoin($userModel->getTable(), $userModel->getQualifiedKeyName(),
                $addressModel->user()->getQualifiedForeignKeyName())
            ->when($this->gate->denies('viewAnyOwnerEntities', Address::class),
                static function (Builder $builder) use ($user): void {
                    $builder->whereBelongsTo($user);
                })
            ->select($addressModel->qualifyColumn('*'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch),
            )
            ->allowOrderFields(
                'created_at',
                'country',
                'address_type',
                'city',
                'post_code',
                'state',
                'street_address',
                'user_fullname'
            )
            ->qualifyOrderFields(
                created_at: $addressModel->qualifyColumn('created_at'),
                country: $countryModel->qualifyColumn('name'),
                address_type: $addressModel->qualifyColumn('address_type'),
                city: $addressModel->qualifyColumn('city'),
                post_code: $addressModel->qualifyColumn('post_code'),
                state: $addressModel->qualifyColumn('state'),
                street_address: $addressModel->qualifyColumn('address_1'),
                user_fullname: $userModel->qualifyColumn('user_fullname'),
            )
            ->enforceOrderBy($addressModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }
}

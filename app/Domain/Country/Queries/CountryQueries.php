<?php

namespace App\Domain\Country\Queries;

use App\Domain\Company\Models\Company;
use App\Domain\Country\Models\Country;
use App\Domain\Vendor\Models\Vendor;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

final class CountryQueries
{
    public function __construct(
        private readonly Client $elasticsearch,
    ) {
    }

    public function paginateCountriesQuery(Request $request = new Request()): Builder
    {
        $model = new Country();

        $query = $model->newQuery()
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for($query, $request)
            ->allowOrderFields(
                'name',
                'created_at'
            )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->qualifyOrderFields(
                name: $model->qualifyColumn('name'),
                created_at: $model->getQualifiedCreatedAtColumn(),
            )
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function listCountriesByVendor(Vendor $vendor): Builder
    {
        $model = new Country();

        Country::resolveRelationUsing('vendors', static function (Country $model) {
            return $model->belongsToMany(Vendor::class);
        });

        return $model->newQuery()
            ->select($model->qualifyColumns([
                $model->getKeyName(),
                'iso_3166_2',
                'name',
            ]))
            ->whereHas('vendors', static function (Builder $builder) use ($vendor): void {
                $builder->whereKey($vendor);
            });
    }

    public function listCountriesByCompany(Company $company): Builder
    {
        $model = new Country();

        Country::resolveRelationUsing(
            'companies',
            static function (Country $model): HasManyDeep {
                return $model->hasManyDeepFromRelations($model->belongsToMany(Vendor::class), (new Vendor())->companies());
            }
        );

        return $model->newQuery()
            ->select($model->qualifyColumns([
                $model->getKeyName(),
                'iso_3166_2',
                'name',
            ]))
            ->whereHas('companies', static function (Builder $builder) use ($company): void {
                $builder->whereKey($company);
            });
    }

    public function listCountriesOrdered(): Builder
    {
        $model = new Country();

        return $model->newQuery()
            ->orderByRaw('entity_order is null')
            ->orderBy('entity_order')
            ->orderBy('iso_3166_2');
    }
}

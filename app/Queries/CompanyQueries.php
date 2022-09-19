<?php

namespace App\Queries;

use App\Enum\CompanyType;
use App\Http\Query\Company\FilterCompanyCategory;
use App\Models\Asset;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\User;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use App\Queries\Scopes\CurrentUserScope;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;

class CompanyQueries
{

    public function __construct(protected Elasticsearch $elasticsearch,
                                protected Gate          $gate)
    {
    }

    public function listOfAssetCompaniesQuery(Asset $asset): Builder
    {
        $companyModel = $asset->companies()->getRelated();

        $query = $asset->companies()->getQuery()
            ->select([
                $companyModel->getQualifiedKeyName(),
                $companyModel->qualifyColumn('user_id'),
                $companyModel->qualifyColumn('name'),
                $companyModel->qualifyColumn('source'),
                $companyModel->qualifyColumn('type'),
                $companyModel->qualifyColumn('email'),
                $companyModel->qualifyColumn('phone'),
                $companyModel->getQualifiedCreatedAtColumn(),
            ]);

        return tap($query, function (Builder $builder) use ($companyModel) {
            $builder->orderByDesc($companyModel->getQualifiedCreatedAtColumn());
        });
    }

    public function paginateCompaniesQuery(Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var User|null $user */
        $user = $request->user() ?? new User();

        $model = new Company();

        $query = $model->newQuery()
            ->with('image')
            ->select([
                'id',
                'user_id',
                'source',
                'type',
                'name',
                'email',
                'phone',
                'total_quoted_value' => function (BaseBuilder $baseBuilder) {
                    return $baseBuilder
                        ->selectRaw('SUM(total_price)')
                        ->from('quote_totals')
                        ->whereColumn('quote_totals.company_id', 'companies.id');
                },
                'created_at',
                'activated_at',
            ])
            ->withCasts([
                'total_quoted_value' => 'decimal:2',
            ])
            ->tap(CurrentUserScope::from($request, $this->gate))
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(...[
                'created_at',
                'name',
                'vat',
                'phone',
                'website',
                'email',
                'category',
            ])
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function paginateExternalCompaniesQuery(?Request $request = null): Builder
    {
        $request ??= new Request;

        /** @var User|null $user */
        $user = $request->user() ?? new User();

        $model = new Company();

        $query = $model->newQuery()
            ->where($model->qualifyColumn('type'), CompanyType::EXTERNAL)
            ->tap(CurrentUserScope::from($request, $this->gate))
            ->orderByDesc($model->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new PerformElasticsearchSearch($this->elasticsearch),
                new FilterCompanyCategory(),
            )
            ->allowOrderFields(...[
                'created_at',
                'name',
                'vat',
                'phone',
                'website',
                'email',
                'category',
            ])
            ->enforceOrderBy($model->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function listOfInternalCompaniesQuery(): Builder
    {
        $companyModel = new Company();

        return $companyModel->newQuery()
            ->where($companyModel->qualifyColumn('type'), CompanyType::INTERNAL)
            ->whereNotNull($companyModel->qualifyColumn('activated_at'))
            ->orderByRaw("field({$companyModel->qualifyColumn('vat')}, ?, null) desc", [CP_DEF_VAT]);
    }

    public function listOfInternalCompaniesWithCountries(?Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var User|null $user */
        $user = $request->user();

        $companyModel = new Company();

        return $this->listOfInternalCompaniesQuery()
            ->select([
                $companyModel->getQualifiedKeyName(),
                $companyModel->qualifyColumn('name'),
                $companyModel->qualifyColumn('short_code'),
                $companyModel->qualifyColumn('default_country_id'),
            ])
            ->with([
                'countries' => function (Relation $query) use ($user) {
                    $query
                        ->select('countries.id', 'countries.iso_3166_2', 'countries.name', 'countries.flag')
                        ->whereNotNull('vendors.activated_at')
                        ->addSelect([
                            'default_country_id' => function (BaseBuilder$query) {
                                $query->select('default_country_id')
                                    ->from('companies')
                                    ->whereColumn('companies.id', 'company_vendor.company_id');
                            },
                        ])
                        ->orderByRaw('FIELD(countries.id, default_country_id, ?, NULL) DESC', [$user?->country_id]);
                },
            ])
            ->orderByRaw("field({$companyModel->getQualifiedKeyName()}, null, ?) desc", [$user?->company_id]);
    }

    public function listOfExternalCompaniesQuery(Request $request = null): Builder
    {
        $companyModel = new Company();

        return $companyModel->newQuery()
            ->where('type', CompanyType::EXTERNAL)
            ->whereNotNull('activated_at')
            ->tap(CurrentUserScope::from($request ?? new Request(), $this->gate))
            ->orderByRaw("field({$companyModel->qualifyColumn('vat')}, ?, null) desc", [CP_DEF_VAT]);
    }

    public function listOfExternalCompaniesBySource(Request $request = null, string ...$sources): Builder
    {
        return $this->listOfExternalCompaniesQuery($request)
            ->unless(empty($sources), static function (Builder $builder) use ($sources): void {
                $builder->whereIn('source', $sources);
            });
    }
}

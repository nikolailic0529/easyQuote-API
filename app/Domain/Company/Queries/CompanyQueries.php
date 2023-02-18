<?php

namespace App\Domain\Company\Queries;

use App\Domain\Asset\Models\Asset;
use App\Domain\Authorization\Queries\Scopes\CurrentUserScope;
use App\Domain\Company\Enum\CompanyType;
use App\Domain\Company\Models\Company;
use App\Domain\Company\Queries\Filters\FilterCompanyCategory;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Domain\Stats\Models\QuoteTotal;
use App\Domain\User\Models\User;
use App\Foundation\Database\Eloquent\QueryFilter\Enum\OperatorEnum;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\FilterFieldPipe;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\LikeValueProcessor;
use App\Foundation\Database\Eloquent\QueryFilter\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Http\Request;

class CompanyQueries
{
    public function __construct(
        protected Elasticsearch $elasticsearch,
        protected Gate $gate
    ) {
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

    public function baseCompaniesQuery(Request $request = null): Builder
    {
        $request ??= new Request();

        $companyModel = new Company();
        $unitModel = new SalesUnit();

        $query = $companyModel->newQuery()
            ->select([
                $companyModel->getQualifiedKeyName(),
                $companyModel->owner()->getQualifiedForeignKeyName(),
                $companyModel->getQualifiedCreatedAtColumn(),
                ...$companyModel->qualifyColumns([
                    'source',
                    'type',
                    'name',
                    'email',
                    'phone',
                    'activated_at',
                ]),
                "{$unitModel->qualifyColumn('unit_name')} as unit_name",
                'total_quoted_value' => static function (BaseBuilder $builder) use ($companyModel): void {
                    $quoteTotalModel = new QuoteTotal();

                    $builder
                        ->selectRaw('SUM(total_price)')
                        ->from($quoteTotalModel->getTable())
                        ->whereColumn(
                            $quoteTotalModel->company()->getQualifiedForeignKeyName(),
                            $companyModel->getQualifiedKeyName()
                        );
                },
            ])
            ->leftJoin($unitModel->getTable(), $unitModel->getQualifiedKeyName(),
                $companyModel->salesUnit()->getQualifiedForeignKeyName())
            ->with('image')
            ->with('categories')
            ->with('sharingUserRelations')
            ->withCasts([
                'total_quoted_value' => 'decimal:2',
            ])
            ->tap(CurrentUserScope::from($request, $this->gate))
            ->orderByDesc($companyModel->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request,
        )
            ->addCustomBuildQueryPipe(
                new FilterFieldPipe(
                    field: 'sales_unit_id',
                    column: $companyModel->salesUnit()->getQualifiedForeignKeyName(),
                ),
                new FilterFieldPipe(
                    field: 'customer_name',
                    column: $companyModel->qualifyColumn('name'),
                    operator: OperatorEnum::Like,
                    valueProcessor: LikeValueProcessor::new()
                ),
                new FilterFieldPipe(
                    field: 'source',
                    column: $companyModel->qualifyColumn('source'),
                ),
                new PerformElasticsearchSearch($this->elasticsearch)
            )
            ->allowOrderFields(
                'created_at',
                'name',
                'vat',
                'phone',
                'website',
                'email',
                'total_quoted_value',
                'type',
            )
            ->qualifyOrderFields(
                created_at: $companyModel->getQualifiedCreatedAtColumn(),
                name: $companyModel->qualifyColumn('name'),
                vat: $companyModel->qualifyColumn('vat'),
                phone: $companyModel->qualifyColumn('phone'),
                website: $companyModel->qualifyColumn('website'),
                email: $companyModel->qualifyColumn('email'),
                unit_name: $unitModel->qualifyColumn('unit_name'),
            )
            ->enforceOrderBy($companyModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function paginateExternalCompaniesQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        /** @var \App\Domain\User\Models\User|null $user */
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

        /** @var \App\Domain\User\Models\User|null $user */
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
                            'default_country_id' => function (BaseBuilder $query) {
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

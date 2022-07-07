<?php

namespace App\Queries;

use App\Models\Data\Country;
use App\Models\QuoteFile\ImportableColumn;
use App\Queries\Pipeline\PerformElasticsearchSearch;
use Devengine\RequestQueryBuilder\RequestQueryBuilder;
use Elasticsearch\Client as Elasticsearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ImportableColumnQueries
{
    public function __construct()
    {
    }

    public function listOfImportableColumnsQuery(?Request $request = null): Builder
    {
        $request ??= new Request();

        $importableColumnModel = new ImportableColumn();
        $countryModel = new Country();

        $query = $importableColumnModel->newQuery()
            ->select([
                $importableColumnModel->getQualifiedKeyName(),
                $importableColumnModel->user()->getQualifiedForeignKeyName(),
                $importableColumnModel->country()->getQualifiedForeignKeyName(),
                "{$countryModel->qualifyColumn('name')} as country_name",
                $importableColumnModel->qualifyColumn('header'),
                $importableColumnModel->qualifyColumn('type'),
                $importableColumnModel->qualifyColumn('is_system'),
                $importableColumnModel->getQualifiedCreatedAtColumn(),
                $importableColumnModel->qualifyColumn('activated_at'),
            ])
            ->leftJoin($countryModel->getTable(), $countryModel->getQualifiedKeyName(), $importableColumnModel->country()->getQualifiedForeignKeyName())
            ->where($importableColumnModel->qualifyColumn('is_temp'), false)
            ->orderByDesc($importableColumnModel->qualifyColumn('is_active'));

        return RequestQueryBuilder::for(
            builder: $query,
            request: $request
        )
            ->allowQuickSearchFields(...[
                $importableColumnModel->qualifyColumn('header'),
                $importableColumnModel->qualifyColumn('type'),
                $countryModel->qualifyColumn('name'),
            ])
            ->allowOrderFields(...[
                'header',
                'type',
                'country_name',
                'created_at',
            ])
            ->qualifyOrderFields(
                header: $importableColumnModel->qualifyColumn('header'),
                type: $importableColumnModel->qualifyColumn('type'),
                created_at: $importableColumnModel->getQualifiedCreatedAtColumn(),
                country_name: $countryModel->qualifyColumn('name'),
            )
            ->allowQuickSearchFields(...[
                $importableColumnModel->qualifyColumn('header'),
                $importableColumnModel->qualifyColumn('type'),
                $importableColumnModel->getQualifiedCreatedAtColumn(),
                $countryModel->qualifyColumn('name'),
            ])
            ->enforceOrderBy($importableColumnModel->getQualifiedCreatedAtColumn(), 'desc')
            ->process();
    }

    public function documentEngineLinkedImportableColumnsQuery(): Builder
    {
        $model = new ImportableColumn();

        return $model->newQuery()
            ->select([
                $model->getQualifiedKeyName(),
                $model->qualifyColumn('de_header_reference'),
                $model->qualifyColumn('header'),
                $model->qualifyColumn('name'),
                $model->getQualifiedCreatedAtColumn(),
                $model->getQualifiedUpdatedAtColumn()
            ])
            ->whereNotNull('de_header_reference')
            ->with('aliases');
    }
}
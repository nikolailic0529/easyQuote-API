<?php

namespace App\Domain\Worldwide\Queries;

use App\Domain\Asset\Models\Asset;
use App\Domain\Company\Models\Company;
use App\Domain\DocumentMapping\Models\MappedRow;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Worldwide\Models\Opportunity;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

class WorldwideQuoteAssetQueries
{
    public function buildConstraintsForSameWorldwideQuoteAssetRelationship(Builder $builder, Company $primaryAccount): void
    {
        $opportunityModel = new Opportunity();
        $quoteModel = new WorldwideQuote();
        $quoteVersionModel = new WorldwideQuoteVersion();
        $assetModel = new WorldwideQuoteAsset();

        $builder
            ->join($quoteVersionModel->getTable(), $quoteVersionModel->getQualifiedKeyName(), $builder->qualifyColumn($assetModel->worldwideQuote()->getForeignKeyName()))
            ->join($quoteModel->getTable(), $quoteModel->getQualifiedKeyName(), $quoteVersionModel->worldwideQuote()->getQualifiedForeignKeyName())
            ->join($opportunityModel->getTable(), $opportunityModel->getQualifiedKeyName(), $quoteModel->opportunity()->getQualifiedForeignKeyName())
            ->join($primaryAccount->getTable(), $primaryAccount->getQualifiedKeyName(), $opportunityModel->primaryAccount()->getQualifiedForeignKeyName())
            ->where($primaryAccount->getQualifiedKeyName(), '<>', $primaryAccount->getKey())
            ->where(function (Builder $constraints) {
                $constraints->where($constraints->qualifyColumn('is_selected'), true)
                    ->orWhereHas('groups', function (Builder $relation) {
                        $relation->where($relation->qualifyColumn('is_selected'), true);
                    });
            });
    }

    public function buildConstraintsForSameMappedRowRelationship(Builder $builder, Company $primaryAccount): void
    {
        $opportunityModel = new Opportunity();
        $quoteModel = new WorldwideQuote();
        $quoteVersionModel = new WorldwideQuoteVersion();
        $distributorQuoteModel = new WorldwideDistribution();
        $mappedRowModel = new MappedRow();
        $quoteFileModel = new QuoteFile();

        $builder
            ->join($quoteFileModel->getTable(), $quoteFileModel->getQualifiedKeyName(), $builder->qualifyColumn($mappedRowModel->quoteFile()->getForeignKeyName()))
            ->join($distributorQuoteModel->getTable(), $distributorQuoteModel->distributorFile()->getQualifiedForeignKeyName(), $quoteFileModel->getQualifiedKeyName())
            ->join($quoteVersionModel->getTable(), $quoteVersionModel->getQualifiedKeyName(), $distributorQuoteModel->worldwideQuote()->getQualifiedForeignKeyName())
            ->join($quoteModel->getTable(), $quoteModel->getQualifiedKeyName(), $quoteVersionModel->worldwideQuote()->getQualifiedForeignKeyName())
            ->join($opportunityModel->getTable(), $opportunityModel->getQualifiedKeyName(), $quoteModel->opportunity()->getQualifiedForeignKeyName())
            ->join($primaryAccount->getTable(), $primaryAccount->getQualifiedKeyName(), $opportunityModel->primaryAccount()->getQualifiedForeignKeyName())
            ->where($primaryAccount->getQualifiedKeyName(), '<>', $primaryAccount->getKey())
            ->where(function (Builder $constraints) {
                $constraints->where($constraints->qualifyColumn('is_selected'), true)
                    ->orWhereHas('distributionRowsGroups', function (Builder $relation) {
                        $relation->where($relation->qualifyColumn('is_selected'), true);
                    });
            });
    }

    public function sameWorldwideQuoteAssetsQuery(Collection $assets): Builder
    {
        $worldwideQuoteAssetModel = new WorldwideQuoteAsset();

        $tableAlias = 'through_worldwide_quote_assets';

        return $worldwideQuoteAssetModel->newQuery()
            ->join("{$worldwideQuoteAssetModel->getTable()} as $tableAlias", function (JoinClause $join) use ($tableAlias, $worldwideQuoteAssetModel) {
                $join
                    ->on("$tableAlias.{$worldwideQuoteAssetModel->getKeyName()}", '<>', $worldwideQuoteAssetModel->getQualifiedKeyName())
                    ->on("$tableAlias.serial_no", $worldwideQuoteAssetModel->qualifyColumn('serial_no'))
                    ->on("$tableAlias.sku", $worldwideQuoteAssetModel->qualifyColumn('sku'));
            })
            ->whereIn("$tableAlias.{$worldwideQuoteAssetModel->getKeyName()}", $assets->modelKeys())
            ->select($worldwideQuoteAssetModel->qualifyColumn('*'));
    }

    public function sameMappedRowsQuery(Collection $assets): Builder
    {
        $worldwideQuoteAssetModel = new WorldwideQuoteAsset();
        $mappedRowModel = new MappedRow();

        return $mappedRowModel->newQuery()
            ->join($worldwideQuoteAssetModel->getTable(), function (JoinClause $join) use ($mappedRowModel, $worldwideQuoteAssetModel) {
                $join
                    ->on($mappedRowModel->qualifyColumn('serial_no'), $worldwideQuoteAssetModel->qualifyColumn('serial_no'))
                    ->on($mappedRowModel->qualifyColumn('product_no'), $worldwideQuoteAssetModel->qualifyColumn('sku'));
            })
            ->whereIn($worldwideQuoteAssetModel->getQualifiedKeyName(), $assets->modelKeys())
            ->select($mappedRowModel->qualifyColumn('*'));
    }

    public function sameGenericAssetsQuery(Collection $assets): Builder
    {
        $worldwideQuoteAssetModel = new WorldwideQuoteAsset();
        $genericAssetModel = new Asset();

        return $genericAssetModel->newQuery()
            ->join($worldwideQuoteAssetModel->getTable(), function (JoinClause $join) use ($genericAssetModel, $worldwideQuoteAssetModel) {
                $join
                    ->on($genericAssetModel->qualifyColumn('serial_number'), $worldwideQuoteAssetModel->qualifyColumn('serial_no'))
                    ->on($genericAssetModel->qualifyColumn('product_number'), $worldwideQuoteAssetModel->qualifyColumn('sku'));
            })
            ->whereIn($worldwideQuoteAssetModel->getQualifiedKeyName(), $assets->modelKeys())
            ->select($genericAssetModel->qualifyColumn('*'));
    }
}

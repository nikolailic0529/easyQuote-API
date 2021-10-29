<?php

namespace App\Queries;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\MappedRow;
use App\Models\QuoteFile\QuoteFile;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;

class MappedRowQueries
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

    public function sameMappedRowsQuery(Collection $rows): Builder
    {
        $mappedRowModel = new MappedRow();

        $mappedRowTableAlias = 'through_mapped_rows';

        return $mappedRowModel->newQuery()
            ->join("{$mappedRowModel->getTable()} as $mappedRowTableAlias", function (JoinClause $join) use ($mappedRowTableAlias, $mappedRowModel) {
                $join
                    ->on("$mappedRowTableAlias.{$mappedRowModel->getKeyName()}", '<>', $mappedRowModel->getQualifiedKeyName())
                    ->on("$mappedRowTableAlias.serial_no", $mappedRowModel->qualifyColumn('serial_no'))
                    ->on("$mappedRowTableAlias.product_no", $mappedRowModel->qualifyColumn('product_no'));
            })
            ->whereIn("$mappedRowTableAlias.{$mappedRowModel->getKeyName()}", $rows->modelKeys())
            ->select($mappedRowModel->qualifyColumn('*'));
    }

    public function sameWorldwideQuoteAssetsQuery(Collection $rows): Builder
    {
        $mappedRowModel = new MappedRow();
        $worldwideQuoteAssetModel = new WorldwideQuoteAsset();

        return $worldwideQuoteAssetModel->newQuery()
            ->join($mappedRowModel->getTable(), function (JoinClause $join) use ($worldwideQuoteAssetModel, $mappedRowModel) {
                $join
                    ->on($worldwideQuoteAssetModel->qualifyColumn('serial_no'), $mappedRowModel->qualifyColumn('serial_no'))
                    ->on($worldwideQuoteAssetModel->qualifyColumn('sku'), $mappedRowModel->qualifyColumn('product_no'));
            })
            ->whereIn($mappedRowModel->getQualifiedKeyName(), $rows->modelKeys())
            ->select($worldwideQuoteAssetModel->qualifyColumn('*'));
    }

    public function sameGenericAssetsQuery(Collection $rows): Builder
    {
        $mappedRowModel = new MappedRow();
        $genericAssetModel = new Asset();

        return $genericAssetModel->newQuery()
            ->join($mappedRowModel->getTable(), function (JoinClause $join) use ($genericAssetModel, $mappedRowModel) {
                $join
                    ->on($genericAssetModel->qualifyColumn('serial_number'), $mappedRowModel->qualifyColumn('serial_no'))
                    ->on($genericAssetModel->qualifyColumn('product_number'), $mappedRowModel->qualifyColumn('product_no'));
            })
            ->whereIn($mappedRowModel->getQualifiedKeyName(), $rows->modelKeys())
            ->select($genericAssetModel->qualifyColumn('*'));
    }
}
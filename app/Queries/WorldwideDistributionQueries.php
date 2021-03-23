<?php

namespace App\Queries;

use App\DTO\DistributionRowsLookupData;
use App\Models\Quote\WorldwideDistribution;
use App\Models\QuoteFile\DistributionRowsGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;

class WorldwideDistributionQueries
{
    public function distributionQualifiedNameQuery(string $id, string $as = 'qualified_distribution_name'): Builder
    {
        return WorldwideDistribution::addSelect([
            DB::raw("concat(group_concat(vendors.short_code separator ', '), ' > ', countries.iso_3166_2) as `$as`"),
        ])
            ->join('vendor_worldwide_distribution', function (JoinClause $join) {
                $join->on('vendor_worldwide_distribution.worldwide_distribution_id', '=', 'worldwide_distributions.id');
            })
            ->join('vendors', function (JoinClause $join) {
                $join->on('vendors.id', '=', 'vendor_worldwide_distribution.vendor_id');
            })
            ->join('countries', function (JoinClause $join) {
                $join->on('countries.id', '=', 'worldwide_distributions.country_id');
            })
            ->whereKey($id);
    }

    public function distributionTotalPriceQuery(WorldwideDistribution $distribution, string $as = 'total_price'): Builder
    {
        if ($distribution->use_groups) {
            return with($distribution->rowsGroups(), function (HasMany $relation) use ($as) {
                /** @var DistributionRowsGroup */
                $groupModel = $relation->getRelated();
                $rowsRelation = $groupModel->rows();

                return $relation->getQuery()
                ->select(
                    DB::raw('sum(' . $rowsRelation->getRelated()->qualifyColumn('price') . ') as ' . $as)
                )
                // join distribution_rows_group_mapped_row pivot table
                ->join($rowsRelation->getTable(), function (JoinClause $join) use ($rowsRelation, $groupModel) {
                    $join->on($rowsRelation->getQualifiedForeignPivotKeyName(), $groupModel->getQualifiedKeyName());
                })
                // join mapped_rows table
                ->join($rowsRelation->getRelated()->getTable(), function (JoinClause $join) use ($rowsRelation) {
                    $join->on($rowsRelation->getRelated()->getQualifiedKeyName(), $rowsRelation->getQualifiedRelatedPivotKeyName());
                })
                ->where($groupModel->qualifyColumn('is_selected'), true)
                ->withCasts([
                    $as => 'decimal:2'
                ]);
            });
        }

        return with($distribution->mappedRows(), function (HasManyDeep $relation) use ($as) {
            return $relation->getQuery()
                ->select(
                    DB::raw('sum(' . $relation->getRelated()->qualifyColumn('price') . ') as ' . $as)
                )
                ->where($relation->getRelated()->qualifyColumn('is_selected'), true)
                ->withCasts([
                    $as => 'decimal:2'
                ]);
        });
    }

    public function rowsLookupQuery(WorldwideDistribution $distribution, DistributionRowsLookupData $data): Builder
    {
        $selectColumns = [
            'mapped_rows.id',
            'mapped_rows.product_no',
            'mapped_rows.description',
            'mapped_rows.serial_no',
            'mapped_rows.date_from',
            'mapped_rows.date_to',
            'mapped_rows.qty',
            'mapped_rows.price',
            'mapped_rows.pricing_document',
            'mapped_rows.system_handle',
            'mapped_rows.searchable',
            'mapped_rows.service_level_description',
            'mapped_rows.is_selected',
        ];

        return $distribution->mappedRows()->getQuery()
            ->where(function (Builder $builder) use ($data) {
                $input = array_values($data->input);

                $columns = ['product_no', 'description', 'serial_no', 'price', 'pricing_document', 'system_handle', 'searchable', 'service_level_description'];

                foreach ($input as $string) {
                    foreach ($columns as $column) {
                        $builder->orWhere($column, 'like', "%$string%");
                    }
                }
            })
            ->select($selectColumns)
            ->when($data->rows_group instanceof DistributionRowsGroup, function (Builder $builder) use ($data, $selectColumns) {
                $builder->union(
                    $data->rows_group->rows()->getQuery()->select($selectColumns)
                );
            });
    }
}

<?php

namespace App\Queries;

use App\DTO\VendorsAndCountryData;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\WorldwideDistribution;
use App\Queries\Exceptions\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class DiscountQueries
{
    protected ValidatorInterface $validator;

    public function __construct(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    public function activeMultiYearDiscountsQuery(): Builder
    {
        return MultiYearDiscount::query()
            ->select(['id', 'name', 'durations'])
            ->whereNotNull('activated_at');
    }

    public function activePrePayDiscountsQuery(): Builder
    {
        return PrePayDiscount::query()
            ->select(['id', 'name', 'durations'])
            ->whereNotNull('activated_at');
    }

    public function activePromotionalDiscountsQuery(): Builder
    {
        return PromotionalDiscount::query()
            ->select(['id', 'name', 'value', 'minimum_limit'])
            ->whereNotNull('activated_at');
    }

    public function activeSnDiscountsQuery(): Builder
    {
        return SND::query()
            ->select(['id', 'name', 'value'])
            ->whereNotNull('activated_at');
    }

    public function discountsForVendorsAndCountryQuery(VendorsAndCountryData $data): BaseBuilder
    {
        $violations = $this->validator->validate($data);

        if (count($violations)) {
            throw new ValidationException($violations);
        }

        $models = [MultiYearDiscount::class, PrePayDiscount::class, PromotionalDiscount::class, SND::class];
        $queries = [];

        foreach ($models as $model) {
            $discountType = addslashes($model);

            $queries[] = $model::whereHas('country', fn(Builder $builder) => $builder->whereKey($data->country_id))
                ->whereHas('vendor', fn(Builder $builder) => $builder->whereKey($data->vendor_keys))
                ->select(
                    'id',
                    DB::raw("'$discountType' as discount_type")
                )
                ->toBase();
        }

        $query = array_shift($queries);

        foreach ($queries as $unionQuery) {
            $query->unionAll($unionQuery);
        }

        return $query;
    }

    public function applicableMultiYearDiscountsForDistributionQuery(WorldwideDistribution $distribution): Builder
    {
        $vendorsRelation = $distribution->vendors();

        return MultiYearDiscount::whereHas('country', function (Builder $builder) use ($distribution) {
            $builder->whereKey($distribution->country_id);
        })->join($vendorsRelation->getTable(), function (JoinClause $join) use ($distribution, $vendorsRelation) {
            $join->on($vendorsRelation->getQualifiedRelatedPivotKeyName(), (new MultiYearDiscount())->vendor()->getQualifiedForeignKeyName())
                ->where($vendorsRelation->getQualifiedForeignPivotKeyName(), $distribution->getKey());
        });
    }

    public function applicablePrePayDiscountsForDistributionQuery(WorldwideDistribution $distribution): Builder
    {
        $vendorsRelation = $distribution->vendors();

        return PrePayDiscount::whereHas('country', function (Builder $builder) use ($distribution) {
            $builder->whereKey($distribution->country_id);
        })->join($vendorsRelation->getTable(), function (JoinClause $join) use ($distribution, $vendorsRelation) {
            $join->on($vendorsRelation->getQualifiedRelatedPivotKeyName(), (new PrePayDiscount())->vendor()->getQualifiedForeignKeyName())
                ->where($vendorsRelation->getQualifiedForeignPivotKeyName(), $distribution->getKey());
        });
    }

    public function applicablePromotionalDiscountsForDistributionQuery(WorldwideDistribution $distribution): Builder
    {
        $vendorsRelation = $distribution->vendors();

        return PromotionalDiscount::whereHas('country', function (Builder $builder) use ($distribution) {
            $builder->whereKey($distribution->country_id);
        })->join($vendorsRelation->getTable(), function (JoinClause $join) use ($distribution, $vendorsRelation) {
            $join->on($vendorsRelation->getQualifiedRelatedPivotKeyName(), (new PromotionalDiscount())->vendor()->getQualifiedForeignKeyName())
                ->where($vendorsRelation->getQualifiedForeignPivotKeyName(), $distribution->getKey());
        });
    }

    public function applicableSnDiscountsForDistributionQuery(WorldwideDistribution $distribution): Builder
    {
        $vendorsRelation = $distribution->vendors();

        return SND::whereHas('country', function (Builder $builder) use ($distribution) {
            $builder->whereKey($distribution->country_id);
        })->join($vendorsRelation->getTable(), function (JoinClause $join) use ($distribution, $vendorsRelation) {
            $join->on($vendorsRelation->getQualifiedRelatedPivotKeyName(), (new SND())->vendor()->getQualifiedForeignKeyName())
                ->where($vendorsRelation->getQualifiedForeignPivotKeyName(), $distribution->getKey());
        });
    }
}

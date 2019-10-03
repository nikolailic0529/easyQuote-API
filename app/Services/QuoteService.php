<?php namespace App\Services;

use App\Contracts\Services\QuoteServiceInterface;
use App\Models\Quote \ {
    Quote,
    Discount,
    Margin\CountryMargin
};
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class QuoteService implements QuoteServiceInterface
{
    public function interact(Quote $quote, $model): Quote
    {
        if($model instanceof CountryMargin) {
            return $this->interactWithCountryMargin($quote, $model);
        }
        if($model instanceof Discount) {
            return $this->interactWithDiscount($quote, $model);
        }

        return $quote;
    }

    public function interactWithCountryMargin(Quote $quote, CountryMargin $countryMargin): Quote
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        $mapping = $quote->mapping;

        if($countryMargin->isPercentage()) {
            $quote->computableRows->transform(function ($row) use ($countryMargin, $mapping) {
                $dateFromColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_from');
                $dateToColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_to');
                $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

                $priceColumn->value = $countryMargin->calculate($priceColumn->value, $dateFromColumn->value ?? null, $dateToColumn->value ?? null);

                return $row;
            });
        }

        $list_price = $this->countTotalPrice($quote->computableRows, $mapping);

        if($countryMargin->isFixed()) {
            $list_price = $countryMargin->calculate($list_price);
        }

        $quote->list_price = $list_price;

        return $quote;
    }

    public function interactWithDiscount(Quote $quote, Discount $discount): Quote
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        if(!isset($quote->list_price) || $quote->list_price === 0.00) {
            $quote->list_price = $this->countTotalPrice($quote->computableRows, $quote->mapping);
        }

        $mapping = $quote->mapping;

        $quote->computableRows->transform(function ($row) use ($discount, $mapping, $quote) {
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $discountValue = $discount->calculateDiscount($priceColumn->value, $quote->list_price);
            $priceColumn->value = ((float) $priceColumn->value) - $discountValue;

            $quote->applicable_discounts += $discountValue;

            return $row;
        });

        return $quote;
    }

    public function countTotalPrice(EloquentCollection $rows, Collection $mapping)
    {
        $total = $rows->reduce(function ($carry, $row) use ($mapping) {
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            return $carry + (float) $priceColumn->value;
        });

        return $total;
    }

    public function getRowColumn(Collection $mapping, EloquentCollection $columnsData, string $name)
    {
        if(!$mapping->has($name)) {
            return null;
        }

        return $columnsData->where('importable_column_id', $mapping->get($name))->first();
    }
}

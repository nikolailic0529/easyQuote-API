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
        if(!isset($quote->computableRows) || !isset($quote->list_price)) {
            return $quote;
        }

        $mapping = $quote->mapping;

        $quote->computableRows->transform(function ($row) use ($discount, $mapping, $quote) {
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');
            $initialValue = $priceColumn->value;
            $priceColumn->value = $discount->calculate($priceColumn->value, $quote->list_price);

            $quote->applicable_discounts = $quote->raw_applicable_discounts + ((float) $initialValue - (float) $priceColumn->value);

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

        return number_format($total, 2);
    }

    private function getRowColumn(Collection $mapping, EloquentCollection $columnsData, string $name)
    {
        if(!$mapping->has($name)) {
            return null;
        }

        return $columnsData->where('importable_column_id', $mapping->get($name))->first();
    }
}

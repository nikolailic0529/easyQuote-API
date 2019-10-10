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
use Carbon\Carbon;

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

        if($countryMargin->isPercentage() && $countryMargin->isNoMargin()) {
            $quote->computableRows->transform(function ($row) use ($countryMargin, $mapping) {
                $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

                $this->checkRequiredFields([$priceColumn]);

                $priceColumn->value = $countryMargin->calculate($priceColumn->value);

                return $row;
            });
        }

        $list_price = $this->countTotalPrice($quote->computableRows, $mapping);

        if($countryMargin->isFixed() && $countryMargin->isNoMargin()) {
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
            $dateFromColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_from');
            $dateToColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_to');
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $this->checkRequiredFields([$dateFromColumn, $dateToColumn, $priceColumn]);

            if($quote->calculate_list_price) {
                $value = $this->diffInDays($dateFromColumn->value, $dateToColumn->value) * ((float) $priceColumn->value / 30);
            } else {
                $value = $priceColumn->value;
            }

            $discountValue = $discount->calculateDiscount($value, $quote->list_price);

            $quote->applicable_discounts += $discountValue;

            return $row;
        });

        return $quote;
    }

    public function countTotalPrice(EloquentCollection $rows, Collection $mapping)
    {
        $total = $rows->reduce(function ($carry, $row) use ($mapping) {
            $dateFromColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_from');
            $dateToColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_to');
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $this->checkRequiredFields([$dateFromColumn, $dateToColumn, $priceColumn]);

            $value = $this->diffInDays($dateFromColumn->value, $dateToColumn->value) * ((float) $priceColumn->value / 30);

            return $carry + $value;
        });

        return $total;
    }

    public function transformPricesBasedOnCoverages(Quote $quote)
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        $mapping = $quote->mapping;

        $quote->computableRows->transform(function ($row) use ($mapping) {
            $dateFromColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_from');
            $dateToColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_to');
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $this->checkRequiredFields([$dateFromColumn, $dateToColumn, $priceColumn]);

            $priceColumn->value = $this->diffInDays($dateFromColumn->value, $dateToColumn->value) * ((float) $priceColumn->value / 30);
            return $row;
        });

        return $quote;
    }

    public function getRowColumn(Collection $mapping, EloquentCollection $columnsData, string $name)
    {
        if(!$mapping->has($name)) {
            return null;
        }

        return $columnsData->where('importable_column_id', $mapping->get($name))->first();
    }

    private function checkRequiredFields(array $columns)
    {
        foreach ($columns as $column) {
            if(!isset($column)) {
                throw new \ErrorException(__('quote.required_fields_exception'));
            }
        }
    }

    private function diffInDays(string $dateFrom, string $dateTo)
    {
        $dateFrom = Carbon::parse(str_replace('/', '.', $dateFrom));
        $dateTo = Carbon::parse(str_replace('/', '.', $dateTo));

        try {
            return $dateFrom->diffInDays($dateTo);
        } catch (\Exception $e) {
            return 0;
        }
    }
}

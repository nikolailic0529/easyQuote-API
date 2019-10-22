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
use Str;

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
        $margin_percentage = $quote->margin_percentage;

        if($countryMargin->isPercentage() && $countryMargin->isNoMargin()) {
            $quote->computableRows->transform(function ($row) use ($countryMargin, $mapping) {
                $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

                $this->checkRequiredFields([$priceColumn]);

                $priceColumn->value = $countryMargin->calculate($priceColumn->value);

                return $row;
            });
        }

        $list_price = $this->countTotalPrice($quote);

        if($countryMargin->isFixed() && $countryMargin->isNoMargin()) {
            $list_price = $countryMargin->calculate($list_price);
        }

        $quote->list_price = $list_price;

        return $quote;
    }

    public function interactWithMargin(Quote $quote): Quote
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        $mapping = $quote->mapping;
        $divider = (100 - $quote->margin_percentage) / 100;

        $quote->computableRows->transform(function ($row) use ($mapping, $divider) {
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $this->checkRequiredFields([$priceColumn]);

            if($divider === 0.00) {
                $priceColumn->value = 0.00;
            } else {
                $priceColumn->value = round(Str::price($priceColumn->value) / $divider, 2);
            }

            return $row;
        });

        return $quote;
    }

    public function interactWithDiscount(Quote $quote, Discount $discount): Quote
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        if(!isset($quote->list_price) || $quote->list_price === 0.00) {
            $quote->list_price = $this->countTotalPrice($quote);
        }

        $mapping = $quote->mapping;

        $quote->computableRows->transform(function ($row) use ($discount, $mapping, $quote) {
            $dateFromColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_from');
            $dateToColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_to');
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $this->checkRequiredFields([$dateFromColumn, $dateToColumn, $priceColumn]);

            if($quote->calculate_list_price) {
                $value = $this->diffInDays($dateFromColumn->value, $dateToColumn->value) * ($priceColumn->value / 30);
            } else {
                $value = $priceColumn->value;
            }

            $discountValue = $discount->calculateDiscount($value, $quote->list_price);

            $quote->applicable_discounts += $discountValue;

            return $row;
        });

        return $quote;
    }

    public function countTotalPrice(Quote $quote)
    {
        if(!isset($quote->computableRows)) {
            return 0;
        }

        $mapping = $quote->mapping;

        $total = $quote->computableRows->reduce(function ($carry, $row) use ($quote, $mapping) {
            $dateFromColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_from');
            $dateToColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_to');
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $this->checkRequiredFields([$dateFromColumn, $dateToColumn, $priceColumn]);

            if($quote->calculate_list_price) {
                $value = $this->diffInDays($dateFromColumn->value, $dateToColumn->value) * ($priceColumn->value / 30);
            } else {
                $value = $priceColumn->value;
            }

            return $carry + $value;
        });

        return $total;
    }

    public function transformPricesBasedOnCoverages(Quote $quote)
    {
        if(!isset($quote->computableRows) || !$quote->calculate_list_price) {
            return $quote;
        }

        $mapping = $quote->mapping;

        $quote->computableRows->transform(function ($row) use ($mapping) {
            $dateFromColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_from');
            $dateToColumn = $this->getRowColumn($mapping, $row->columnsData, 'date_to');
            $priceColumn = $this->getRowColumn($mapping, $row->columnsData, 'price');

            $this->checkRequiredFields([$dateFromColumn, $dateToColumn, $priceColumn]);

            $priceColumn->value = $this->diffInDays($dateFromColumn->value, $dateToColumn->value) * ($priceColumn->value / 30);

            return $row;
        });

        return $quote;
    }

    public function calculateSchedulePrices(Quote $quote): Quote
    {
        if(!isset($quote->scheduleData->value)) {
            return $quote;
        }

        $divider = (100 - $quote->margin_percentage) / 100;

        $quote->scheduleData->value = collect($quote->scheduleData->value)->map(function ($payment) use ($divider) {
            if($divider === 0.00) {
                $payment['price'] = 0.00;
            } else {
                $payment['price'] = round(Str::price($payment['price']) / $divider, 2);
            }

            return $payment;
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

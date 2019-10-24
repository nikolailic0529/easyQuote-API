<?php namespace App\Services;

use App\Contracts\Services\QuoteServiceInterface;
use App\Models\Quote \ {
    Quote,
    Discount,
    Margin\CountryMargin
};
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Str;

class QuoteService implements QuoteServiceInterface
{
    public function interact(Quote $quote, $interactable): Quote
    {
        if($interactable instanceof CountryMargin) {
            return $this->interactWithCountryMargin($quote, $interactable);
        }
        if($interactable instanceof Discount) {
            return $this->interactWithDiscount($quote, $interactable);
        }
        if(is_array($interactable)) {
            collect($interactable)->each(function ($entity) use ($quote) {
                $this->interact($quote, $entity);
            });
        }

        return $quote;
    }

    public function interactWithCountryMargin(Quote $quote, CountryMargin $countryMargin): Quote
    {
        if(!isset($quote->computableRows)) {
            return $quote;
        }

        if($countryMargin->isPercentage() && $countryMargin->isNoMargin()) {
            $quote->computableRows->transform(function ($row) use ($countryMargin) {
                $row->price = $countryMargin->calculate($row->price);
                return $row;
            });
        }

        $list_price = $quote->countTotalPrice();

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

        $divider = (100 - $quote->margin_percentage_without_discounts) / 100;

        if($divider === 0.00) {
            $quote->computableRows->transform(function ($row) {
                $row->price = 0.00;
                return $row;
            });

            $quote->list_price = 0.00;
            return $quote;
        }

        $quote->list_price = 0;

        $quote->computableRows->transform(function ($row) use ($divider, $quote) {
            $row->price = round($row->price / $divider, 2);
            $quote->list_price += $row->price;
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
            $quote->list_price = $quote->countTotalPrice();
        }

        $quote->computableRows->transform(function ($row) use ($discount, $quote) {
            if(!isset($row->computablePrice)) {
                $row->computablePrice = $row->price;
            }

            $discountValue = $discount->calculateDiscount($row->computablePrice, $quote->list_price);
            $quote->applicable_discounts += $discountValue;

            $row->computablePrice -= $discountValue;
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

        if($divider === 0.00) {
            $quote->scheduleData->value = collect($quote->scheduleData->value)->map(function ($payment) {
                $payment['price'] = 0.00;
                return $payment;
            });

            return $quote;
        }

        $quote->scheduleData->value = collect($quote->scheduleData->value)->map(function ($payment) use ($divider) {
            $payment['price'] = round(Str::price($payment['price']) / $divider, 2);
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
}

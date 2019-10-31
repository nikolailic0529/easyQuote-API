<?php namespace App\Services;

use App\Contracts\Services\QuoteServiceInterface;
use App\Models\Quote \ {
    Quote,
    Discount,
    Margin\CountryMargin
};
use Str;

class QuoteService implements QuoteServiceInterface
{
    public function interact(Quote $quote, $interactable): Quote
    {
        if($interactable instanceof CountryMargin) {
            return $this->interactWithCountryMargin($quote, $interactable);
        }
        if($interactable instanceof Discount || is_numeric($interactable)) {
            return $this->interactWithDiscount($quote, $interactable);
        }
        if(is_iterable($interactable)) {
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
        if(!isset($quote->computableRows) || !isset($quote->countryMargin)) {
            return $quote;
        }

        $divider = (100 - $quote->countryMargin->value) / 100;

        if((float) $divider === 0.0) {
            $quote->computableRows->transform(function ($row) {
                $row->price = 0.0;
                return $row;
            });

            $quote->list_price = 0.0;
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

    public function interactWithDiscount(Quote $quote, $discount): Quote
    {
        if(!isset($quote->computableRows) || $discount === 0.0) {
            return $quote;
        }

        if(!isset($quote->list_price) || (float) $quote->list_price === 0.0) {
            $quote->list_price = $quote->countTotalPrice();
        }

        $quote->computableRows->transform(function ($row) use ($discount, $quote) {
            if(!isset($row->computablePrice)) {
                $row->computablePrice = $row->price;
            }

            $value = $this->calculateDiscountValue($discount, (float) $row->computablePrice, (float) $quote->list_price);
            $quote->applicable_discounts += $value;
            $row->computablePrice -= $value;

            return $row;
        });

        return $quote;
    }

    public function calculateSchedulePrices(Quote $quote): Quote
    {
        if(!isset($quote->scheduleData->value) || !isset($quote->countryMargin)) {
            return $quote;
        }

        $divider = (100 - $quote->countryMargin->value) / 100;

        if($divider === 0.0) {
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

    private function calculateDiscountValue($discount, float $price, float $list_price): float
    {
        if($discount instanceof Discount) {
            return $discount->calculateDiscount($price, $list_price);
        }

        if(is_numeric($discount)) {
            return $price * $discount / 100;
        }

        return 0.0;
    }
}

<?php

namespace App\Http\Requests\WorldwideQuote;

use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class ShowPackQuoteApplicableDiscounts extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            //
        ];
    }

    public function getApplicableDiscounts(): Collection
    {
        $discounts = new Collection();

        $discounts = $discounts->merge(MultiYearDiscount::query()->get());
        $discounts = $discounts->merge(PrePayDiscount::query()->get());
        $discounts = $discounts->merge(PromotionalDiscount::query()->get());
        $discounts = $discounts->merge(SND::query()->get());

        return $discounts;
    }
}

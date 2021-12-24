<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\Discounts\ApplicablePredefinedDiscounts;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Services\WorldwideQuote\Calculation\WorldwideDistributorQuoteCalc;
use App\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowMarginAfterPredefinedDiscounts extends FormRequest
{
    protected ?ApplicablePredefinedDiscounts $applicableDiscounts = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'multi_year_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(MultiYearDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'pre_pay_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PrePayDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'promotional_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PromotionalDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'sn_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(SND::class, 'id')->whereNull('deleted_at'),
            ],
        ];
    }

    public function getApplicableDiscounts(): ApplicablePredefinedDiscounts
    {
        return $this->applicableDiscounts ??= new ApplicablePredefinedDiscounts([
            'multi_year_discount' => transform($this->input('multi_year_discount'), function (string $modelKey) {
                /** @var MultiYearDiscount $discount */
                $discount = MultiYearDiscount::query()->findOrFail($modelKey);

                return WorldwideQuoteCalc::multiYearDiscountToImmutableMultiYearDiscountData(
                    $discount
                );
            }),
            'pre_pay_discount' => transform($this->input('pre_pay_discount'), function (string $modelKey) {
                /** @var PrePayDiscount $discount */

                $discount = PrePayDiscount::query()->findOrFail($modelKey);

                return WorldwideQuoteCalc::prePayDiscountToImmutablePrePayDiscountData(
                    $discount
                );
            }),
            'promotional_discount' => transform($this->input('promotional_discount'), function (string $modelKey) {
                /** @var PromotionalDiscount $discount */

                $discount = PromotionalDiscount::query()->findOrFail($modelKey);

                return WorldwideQuoteCalc::promotionalDiscountToImmutablePromotionalDiscountData(
                    $discount
                );
            }),
            'special_negotiation_discount' => transform($this->input('sn_discount'), function (string $modelKey) {
                /** @var SND $discount */

                $discount = SND::query()->findOrFail($modelKey);

                return WorldwideQuoteCalc::snDiscountToImmutableSpecialNegotiationData(
                    $discount
                );
            }),
        ]);
    }
}

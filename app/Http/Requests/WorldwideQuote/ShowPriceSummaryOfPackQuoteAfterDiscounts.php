<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\Discounts\ApplicablePredefinedDiscounts;
use App\DTO\Discounts\CustomDiscountData;
use App\DTO\WorldwideQuote\PackQuoteDiscountData;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Services\WorldwideQuote\WorldwideDistributionCalc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPriceSummaryOfPackQuoteAfterDiscounts extends FormRequest
{
    protected ?PackQuoteDiscountData $discountData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'predefined_discounts' => [
                'bail', 'nullable', 'array'
            ],
            'predefined_discounts.multi_year_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(MultiYearDiscount::class, 'id')->whereNull('deleted_at')
            ],
            'predefined_discounts.pre_pay_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PrePayDiscount::class, 'id')->whereNull('deleted_at')
            ],
            'predefined_discounts.promotional_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PromotionalDiscount::class, 'id')->whereNull('deleted_at')
            ],
            'predefined_discounts.sn_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(SND::class, 'id')->whereNull('deleted_at')
            ],
            'custom_discount' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:100',
                function (string $attribute, $value, \Closure $fail) {
                    if ($this->has('predefined_discounts')) {
                        $fail('Custom discount can not be used with Predefined Discounts.');
                    }
                }
            ],
        ];
    }

    public function getDiscountData(): PackQuoteDiscountData
    {
        return $this->discountData ??= new PackQuoteDiscountData([
            'predefined_discounts' => with($this->input('predefined_discounts') ?? [], function (array $predefinedDiscounts): ApplicablePredefinedDiscounts {
                return new ApplicablePredefinedDiscounts([
                    'multi_year_discount' => transform($predefinedDiscounts['multi_year_discount'] ?? null, function (string $modelKey) {
                        /** @var MultiYearDiscount $model */
                        $model = MultiYearDiscount::query()->findOrFail($modelKey);

                        return WorldwideDistributionCalc::multiYearDiscountToImmutableMultiYearDiscountData($model);
                    }),
                    'pre_pay_discount' => transform($predefinedDiscounts['pre_pay_discount'] ?? null, function (string $modelKey) {
                        /** @var PrePayDiscount $model */
                        $model = PrePayDiscount::query()->findOrFail($modelKey);

                        return WorldwideDistributionCalc::prePayDiscountToImmutablePrePayDiscountData($model);
                    }),
                    'promotional_discount' => transform($predefinedDiscounts['promotional_discount'] ?? null, function (string $modelKey) {
                        /** @var PromotionalDiscount $model */
                        $model = PromotionalDiscount::query()->findOrFail($modelKey);

                        return WorldwideDistributionCalc::promotionalDiscountToImmutablePromotionalDiscountData($model);

                    }),
                    'special_negotiation_discount' => transform($predefinedDiscounts['sn_discount'] ?? null, function (string $modelKey) {
                        /** @var SND $model */
                        $model = SND::query()->findOrFail($modelKey);

                        return WorldwideDistributionCalc::snDiscountToImmutableSpecialNegotiationData($model);
                    }),
                ]);
            }),
            'custom_discount' => transform($this->input('custom_discount'), fn($value) => CustomDiscountData::immutable([
                'value' => (float)$value
            ])),
        ]);
    }


}

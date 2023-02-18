<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Discount\DataTransferObjects\ApplicablePredefinedDiscounts;
use App\Domain\Discount\DataTransferObjects\CustomDiscountData;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Worldwide\DataTransferObjects\Quote\PackQuoteDiscountData;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPriceSummaryOfPackQuoteAfterDiscountsRequest extends FormRequest
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
                'bail', 'nullable', 'array',
            ],
            'predefined_discounts.multi_year_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(MultiYearDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'predefined_discounts.pre_pay_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PrePayDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'predefined_discounts.promotional_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PromotionalDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'predefined_discounts.sn_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(SND::class, 'id')->whereNull('deleted_at'),
            ],
            'custom_discount' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:100',
                function (string $attribute, $value, \Closure $fail) {
                    if ($this->has('predefined_discounts')) {
                        $fail('Custom discount can not be used with Predefined Discounts.');
                    }
                },
            ],
        ];
    }

    public function getDiscountData(): PackQuoteDiscountData
    {
        return $this->discountData ??= new PackQuoteDiscountData([
            'predefined_discounts' => with($this->input('predefined_discounts') ?? [], function (array $predefinedDiscounts): ApplicablePredefinedDiscounts {
                return new ApplicablePredefinedDiscounts([
                    'multi_year_discount' => transform($predefinedDiscounts['multi_year_discount'] ?? null, function (string $modelKey) {
                        /** @var \App\Domain\Discount\Models\MultiYearDiscount $model */
                        $model = MultiYearDiscount::query()->findOrFail($modelKey);

                        return WorldwideQuoteCalc::multiYearDiscountToImmutableMultiYearDiscountData($model);
                    }),
                    'pre_pay_discount' => transform($predefinedDiscounts['pre_pay_discount'] ?? null, function (string $modelKey) {
                        /** @var \App\Domain\Discount\Models\PrePayDiscount $model */
                        $model = PrePayDiscount::query()->findOrFail($modelKey);

                        return WorldwideQuoteCalc::prePayDiscountToImmutablePrePayDiscountData($model);
                    }),
                    'promotional_discount' => transform($predefinedDiscounts['promotional_discount'] ?? null, function (string $modelKey) {
                        /** @var PromotionalDiscount $model */
                        $model = PromotionalDiscount::query()->findOrFail($modelKey);

                        return WorldwideQuoteCalc::promotionalDiscountToImmutablePromotionalDiscountData($model);
                    }),
                    'special_negotiation_discount' => transform($predefinedDiscounts['sn_discount'] ?? null, function (string $modelKey) {
                        /** @var SND $model */
                        $model = SND::query()->findOrFail($modelKey);

                        return WorldwideQuoteCalc::snDiscountToImmutableSpecialNegotiationData($model);
                    }),
                ]);
            }),
            'custom_discount' => transform($this->input('custom_discount'), fn ($value) => CustomDiscountData::immutable([
                'value' => (float) $value,
            ])),
        ]);
    }
}

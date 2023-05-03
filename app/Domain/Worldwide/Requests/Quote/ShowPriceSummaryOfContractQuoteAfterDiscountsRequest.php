<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Discount\DataTransferObjects\ApplicablePredefinedDiscounts;
use App\Domain\Discount\DataTransferObjects\CustomDiscountData;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Worldwide\DataTransferObjects\Quote\DistributorQuoteDiscountCollection;
use App\Domain\Worldwide\DataTransferObjects\Quote\DistributorQuoteDiscountData;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Services\WorldwideQuote\Calculation\WorldwideQuoteCalc;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPriceSummaryOfContractQuoteAfterDiscountsRequest extends FormRequest
{
    protected ?DistributorQuoteDiscountCollection $discountCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'worldwide_distributions' => [
                'required', 'array',
            ],
            'worldwide_distributions.*.worldwide_distribution_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')
                    ->whereNull('deleted_at')
                    ->where('worldwide_quote_id', $this->getWorldwideQuote()->active_version_id),
            ],
            'worldwide_distributions.*.predefined_discounts' => [
                'bail', 'nullable', 'array',
            ],
            'worldwide_distributions.*.predefined_discounts.multi_year_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(MultiYearDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.predefined_discounts.pre_pay_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PrePayDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.predefined_discounts.promotional_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PromotionalDiscount::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.predefined_discounts.sn_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(SND::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.custom_discount' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:100',
                function (string $attribute, $value, \Closure $fail) {
                    if ($this->has('predefined_discounts')) {
                        $fail('Custom discount can not be used with Predefined Discounts.');
                    }
                },
            ],
            'worldwide_distributions.*.index' => [
                'bail', 'integer', 'distinct',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'worldwide_distributions.*.custom_discount.max' => 'Custom Discount may not be greater than :max%.',
        ];
    }

    public function getWorldwideQuote(): WorldwideQuote
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('worldwide_quote');
    }

    public function getDiscountCollection(): DistributorQuoteDiscountCollection
    {
        return $this->discountCollection ??= with(true, function () {
            $collection = array_map(function (array $distributorQuoteDiscountsData) {
                $predefinedDiscountsData = transform($distributorQuoteDiscountsData['predefined_discounts'] ?? null, function (array $predefinedDiscounts) {
                    return [
                        'multi_year_discount' => transform($predefinedDiscounts['multi_year_discount'] ?? null, function (string $modelKey) {
                            /** @var MultiYearDiscount $model */
                            $model = MultiYearDiscount::query()->findOrFail($modelKey);

                            return WorldwideQuoteCalc::multiYearDiscountToImmutableMultiYearDiscountData($model);
                        }),
                        'pre_pay_discount' => transform($predefinedDiscounts['pre_pay_discount'] ?? null, function (string $modelKey) {
                            /** @var PrePayDiscount $model */
                            $model = PrePayDiscount::query()->findOrFail($modelKey);

                            return WorldwideQuoteCalc::prePayDiscountToImmutablePrePayDiscountData($model);
                        }),
                        'promotional_discount' => transform($predefinedDiscounts['promotional_discount'] ?? null, function (string $modelKey) {
                            /** @var PromotionalDiscount $model */
                            $model = PromotionalDiscount::query()->findOrFail($modelKey);

                            return WorldwideQuoteCalc::promotionalDiscountToImmutablePromotionalDiscountData($model);
                        }),
                        'special_negotiation_discount' => transform($predefinedDiscounts['sn_discount'] ?? null, function (string $modelKey) {
                            /** @var \App\Domain\Discount\Models\SND $model */
                            $model = SND::query()->findOrFail($modelKey);

                            return WorldwideQuoteCalc::snDiscountToImmutableSpecialNegotiationData($model);
                        }),
                    ];
                }, []);

                $customDiscount = transform($distributorQuoteDiscountsData['custom_discount'] ?? null, fn ($value) => CustomDiscountData::immutable([
                    'value' => (float) $value,
                ]));

                return new DistributorQuoteDiscountData([
                    'worldwide_distribution' => WorldwideDistribution::query()->find($distributorQuoteDiscountsData['worldwide_distribution_id']),
                    'index' => $distributorQuoteDiscountsData['index'] ?? null,
                    'predefined_discounts' => new ApplicablePredefinedDiscounts($predefinedDiscountsData),
                    'custom_discount' => $customDiscount,
                ]);
            }, $this->input('worldwide_distributions'));

            return new DistributorQuoteDiscountCollection($collection);
        });
    }
}

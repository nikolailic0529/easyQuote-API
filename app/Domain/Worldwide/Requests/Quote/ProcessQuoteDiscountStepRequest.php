<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Discount\DataTransferObjects\PredefinedDiscounts;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\PackDiscountStage;
use App\Domain\Worldwide\Enum\PackQuoteStage;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProcessQuoteDiscountStepRequest extends FormRequest
{
    protected ?PackDiscountStage $quoteStage = null;

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
            'stage' => [
                'bail', 'required', Rule::in(PackQuoteStage::getLabels()),
            ],
        ];
    }

    public function getStage(): PackDiscountStage
    {
        return $this->quoteStage ??= with(true, function () {
            $predefinedDiscounts = transform($this->input('predefined_discounts'), function (array $predefinedDiscounts) {
                return [
                    'multiYearDiscount' => transform($predefinedDiscounts['multi_year_discount'] ?? null, function (string $modelKey) {
                        return MultiYearDiscount::query()->findOrFail($modelKey);
                    }),
                    'prePayDiscount' => transform($predefinedDiscounts['pre_pay_discount'] ?? null, function (string $modelKey) {
                        return PrePayDiscount::query()->findOrFail($modelKey);
                    }),
                    'promotionalDiscount' => transform($predefinedDiscounts['promotional_discount'] ?? null, function (string $modelKey) {
                        return PromotionalDiscount::query()->findOrFail($modelKey);
                    }),
                    'snDiscount' => transform($predefinedDiscounts['sn_discount'] ?? null, function (string $modelKey) {
                        return SND::query()->findOrFail($modelKey);
                    }),
                ];
            }, []);

            $customDiscount = transform($this->input('custom_discount'), fn ($value) => (float) $value);

            return new PackDiscountStage([
                'predefinedDiscounts' => new PredefinedDiscounts($predefinedDiscounts),
                'customDiscount' => $customDiscount,
                'stage' => PackQuoteStage::getValueOfLabel($this->input('stage')),
            ]);
        });
    }
}

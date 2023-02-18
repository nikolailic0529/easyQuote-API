<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Discount\DataTransferObjects\DistributionDiscountsCollection;
use App\Domain\Discount\DataTransferObjects\DistributionDiscountsData;
use App\Domain\Discount\DataTransferObjects\PredefinedDiscounts;
use App\Domain\Discount\Models\MultiYearDiscount;
use App\Domain\Discount\Models\PrePayDiscount;
use App\Domain\Discount\Models\PromotionalDiscount;
use App\Domain\Discount\Models\SND;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractDiscountStage;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ApplyDiscountsRequest extends FormRequest
{
    protected ?WorldwideQuote $worldwideQuote = null;

    protected ?ContractDiscountStage $discountStage = null;

    protected ?DistributionDiscountsCollection $distributionDiscountsCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'worldwide_distributions' => [
                'bail', 'required', 'array',
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')
                    ->where('worldwide_quote_id', $this->getQuote()->active_version_id)
                    ->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.predefined_discounts' => [
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
            'custom_discount' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:100',
                function (string $attribute, $value, \Closure $fail) {
                    if ($this->has('predefined_discounts')) {
                        $fail('Custom discount can not be used with Predefined Discounts.');
                    }
                },
            ],
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels()),
            ],
        ];
    }

    public function getStage(): ContractDiscountStage
    {
        return $this->discountStage ??= new ContractDiscountStage([
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage')),
            'distributionDiscounts' => $this->getDistributionDiscountsCollection(),
        ]);
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->worldwideQuote ??= with(true, function (): WorldwideQuote {
            /** @var WorldwideQuoteVersion $version */
            $version = WorldwideQuoteVersion::query()->whereHas('worldwideDistributions', function (Builder $builder) {
                $builder->whereKey($this->input('worldwide_distributions.*.id'));
            })->sole();

            return $version->worldwideQuote;
        });
    }

    public function getDistributionDiscountsCollection(): DistributionDiscountsCollection
    {
        return $this->distributionDiscountsCollection ??= with(true, function (): DistributionDiscountsCollection {
            $collection = array_map(function (array $distribution) {
                $predefinedDiscounts = transform($distribution['predefined_discounts'] ?? null, function (array $predefinedDiscounts) {
                    return [
                        'multiYearDiscount' => transform($predefinedDiscounts['multi_year_discount'] ?? null, function (string $modelKey) {
                            return MultiYearDiscount::findOrFail($modelKey);
                        }),
                        'prePayDiscount' => transform($predefinedDiscounts['pre_pay_discount'] ?? null, function (string $modelKey) {
                            return PrePayDiscount::findOrFail($modelKey);
                        }),
                        'promotionalDiscount' => transform($predefinedDiscounts['promotional_discount'] ?? null, function (string $modelKey) {
                            return PromotionalDiscount::findOrFail($modelKey);
                        }),
                        'snDiscount' => transform($predefinedDiscounts['sn_discount'] ?? null, function (string $modelKey) {
                            return SND::findOrFail($modelKey);
                        }),
                    ];
                }, []);

                return new DistributionDiscountsData([
                    'worldwideDistribution' => WorldwideDistribution::findOrFail($distribution['id']),
                    'predefinedDiscounts' => new PredefinedDiscounts($predefinedDiscounts),
                    'customDiscount' => transform($distribution['custom_discount'] ?? null, fn ($value) => (float) $value),
                ]);
            }, $this->input('worldwide_distributions'));

            return new DistributionDiscountsCollection($collection);
        });
    }
}

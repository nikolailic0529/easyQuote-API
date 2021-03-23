<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\Discounts\DistributionDiscountsCollection;
use App\DTO\Discounts\DistributionDiscountsData;
use App\DTO\Discounts\PredefinedDiscounts;
use App\DTO\QuoteStages\ContractDiscountStage;
use App\Enum\ContractQuoteStage;
use App\Models\Quote\Discount\MultiYearDiscount;
use App\Models\Quote\Discount\PrePayDiscount;
use App\Models\Quote\Discount\PromotionalDiscount;
use App\Models\Quote\Discount\SND;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ApplyDiscounts extends FormRequest
{
    protected ?WorldwideQuote $worldwideQuote = null;

    protected ?ContractDiscountStage $discountStage = null;

    protected ?DistributionDiscountsCollection $distributionDiscountsCollection = null;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $modelKeys = $this->input('worldwide_distributions.*.id');

        $quoteKeys = WorldwideDistribution::whereKey($modelKeys)->distinct('worldwide_quote_id')->toBase()->pluck('worldwide_quote_id');

        if ($quoteKeys->count() > 1) {
            throw new AuthorizationException('The processable entities must belong to the same Worldwide Quote', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        Gate::authorize('update', [$wwQuote = WorldwideQuote::whereKey($quoteKeys)->firstOrFail()]);

        if ($wwQuote->submitted_at !== null) {
            throw new AuthorizationException('You can\'t update a state of submitted Worldwide Quote', Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'worldwide_distributions' => [
                'bail', 'required', 'array'
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')->whereNull('deleted_at')
            ],
            'worldwide_distributions.*.predefined_discounts' => [
                //
            ],
            'worldwide_distributions.*.predefined_discounts.multi_year_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(MultiYearDiscount::class, 'id')->whereNull('deleted_at')
            ],
            'worldwide_distributions.*.predefined_discounts.pre_pay_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PrePayDiscount::class, 'id')->whereNull('deleted_at')
            ],
            'worldwide_distributions.*.predefined_discounts.promotional_discount' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(PromotionalDiscount::class, 'id')->whereNull('deleted_at')
            ],
            'worldwide_distributions.*.predefined_discounts.sn_discount' => [
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
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels())
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
        return $this->worldwideQuote ??= WorldwideQuote::whereHas('worldwideDistributions', function (Builder $builder) {
            return $builder->whereKey($this->input('worldwide_distributions.0.id'));
        })->firstOrFail();
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
                    'customDiscount' => transform($distribution['custom_discount'] ?? null, fn($value) => (float)$value)
                ]);
            }, $this->input('worldwide_distributions'));

            return new DistributionDiscountsCollection($collection);
        });
    }
}

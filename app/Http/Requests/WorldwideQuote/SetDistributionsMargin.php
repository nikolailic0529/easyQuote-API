<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\DistributionMarginTax;
use App\DTO\DistributionMarginTaxCollection;
use App\DTO\QuoteStages\ContractMarginTaxStage;
use App\Enum\ContractQuoteStage;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SetDistributionsMargin extends FormRequest
{
    protected ?ContractMarginTaxStage $marginStage = null;

    protected ?DistributionMarginTaxCollection $distributionMarginCollection = null;

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
            'worldwide_distributions.*.tax_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
            'worldwide_distributions.*.margin_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
            'worldwide_distributions.*.quote_type' => [
                'bail', 'required_with:worldwide_distributions.*.margin_value', 'nullable', 'string', 'in:New,Renewal'
            ],
            'worldwide_distributions.*.margin_method' => [
                'bail', 'required_with:worldwide_distributions.*.margin_value', 'nullable', 'string', 'in:No Margin,Standard'
            ],
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels())
            ],
        ];
    }

    public function getStage(): ContractMarginTaxStage
    {
        return $this->marginStage ??= new ContractMarginTaxStage([
            'distributions_margin' => $this->getDistributionMarginCollection(),
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage'))
        ]);
    }

    public function getDistributionMarginCollection(): DistributionMarginTaxCollection
    {
        return $this->distributionMarginCollection ??= with(true, function () {
            $collection = array_map(function (array $distribution) {
                return new DistributionMarginTax([
                    'worldwide_distribution_id' => $distribution['id'],
                    'tax_value' => transform($distribution['tax_value'] ?? null, fn ($value) => (float) $value),
                    'margin_value' => transform($distribution['margin_value'] ?? null, fn ($value) => (float) $value),
                    'quote_type' => $distribution['quote_type'] ?? 'New',
                    'margin_method' => $distribution['margin_method'] ?? 'No Margin'
                ]);
            }, $this->input('worldwide_distributions'));

            return new DistributionMarginTaxCollection($collection);
        });
    }
}

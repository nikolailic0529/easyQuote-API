<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionMarginTax;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionMarginTaxCollection;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ContractMarginTaxStage;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SetDistributionsMarginRequest extends FormRequest
{
    protected ?ContractMarginTaxStage $marginStage = null;

    protected ?DistributionMarginTaxCollection $distributionMarginCollection = null;

    protected ?WorldwideQuote $worldwideQuoteModel = null;

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
            'worldwide_distributions.*.tax_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'. 100_000_000,
            ],
            'worldwide_distributions.*.margin_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'. 100_000_000,
            ],
            'worldwide_distributions.*.quote_type' => [
                'bail', 'required_with:worldwide_distributions.*.margin_value', 'nullable', 'string', 'in:New,Renewal',
            ],
            'worldwide_distributions.*.margin_method' => [
                'bail', 'required_with:worldwide_distributions.*.margin_value', 'nullable', 'string', 'in:No Margin,Standard',
            ],
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels()),
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->worldwideQuoteModel ??= with(true, function (): WorldwideQuote {
            /** @var WorldwideQuoteVersion $version */
            $version = WorldwideQuoteVersion::query()->whereHas('worldwideDistributions', function (Builder $builder) {
                $builder->whereKey($this->input('worldwide_distributions.*.id'));
            })->sole();

            return $version->worldwideQuote;
        });
    }

    public function getStage(): ContractMarginTaxStage
    {
        return $this->marginStage ??= new ContractMarginTaxStage([
            'distributions_margin' => $this->getDistributionMarginCollection(),
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage')),
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
                    'margin_method' => $distribution['margin_method'] ?? 'No Margin',
                ]);
            }, $this->input('worldwide_distributions'));

            return new DistributionMarginTaxCollection($collection);
        });
    }
}

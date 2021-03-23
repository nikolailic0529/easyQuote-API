<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\Margin\MarginTaxData;
use App\DTO\WorldwideQuote\DistributorQuoteCountryMarginTaxCollection;
use App\DTO\WorldwideQuote\DistributorQuoteCountryMarginTaxData;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowPriceSummaryOfContractQuoteAfterCountryMarginTax extends FormRequest
{
    protected ?DistributorQuoteCountryMarginTaxCollection $countryMarginTaxCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'worldwide_distributions' => [
                'required', 'array'
            ],
            'worldwide_distributions.*.worldwide_distribution_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')
                    ->whereNull('deleted_at')
                    ->where('worldwide_quote_id', $this->getWorldwideQuote()->getKey())
            ],
            'worldwide_distributions.*.margin_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
            'worldwide_distributions.*.tax_value' => [
                'bail', 'nullable', 'numeric', 'min:'.(-100_000_000), 'max:'.(100_000_000),
            ],
            'worldwide_distributions.*.index' => [
                'bail', 'integer', 'distinct'
            ]
        ];
    }

    public function getWorldwideQuote(): WorldwideQuote
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('worldwide_quote');
    }

    public function getCountryMarginTaxCollection(): DistributorQuoteCountryMarginTaxCollection
    {
        return $this->countryMarginTaxCollection ??= with(true, function (): DistributorQuoteCountryMarginTaxCollection {
            $collection = array_map(function (array $quoteCountryMarginTaxData) {
                $marginTaxData = MarginTaxData::immutable([
                    'margin_value' => transform($quoteCountryMarginTaxData['margin_value'] ?? null, fn($value) => (float)$value),
                    'tax_value' => transform($quoteCountryMarginTaxData['tax_value'] ?? null, fn($value) => (float)$value),
                ]);

                return new DistributorQuoteCountryMarginTaxData([
                    'worldwide_distribution' => WorldwideDistribution::query()->find($quoteCountryMarginTaxData['worldwide_distribution_id']),
                    'index' => $quoteCountryMarginTaxData['index'] ?? null,
                    'margin_tax_data' => $marginTaxData
                ]);

            }, $this->input('worldwide_distributions'));

            return new DistributorQuoteCountryMarginTaxCollection($collection);
        });
    }
}

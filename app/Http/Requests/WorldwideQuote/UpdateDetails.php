<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\DistributionDetailsCollection;
use App\DTO\DistributionDetailsData;
use App\DTO\QuoteStages\ContractDetailsStage;
use App\Enum\ContractQuoteStage;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class UpdateDetails extends FormRequest
{
    protected ?WorldwideQuote $quote = null;

    protected ?ContractDetailsStage $stage = null;

    protected ?DistributionDetailsCollection $distributionDetailsCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'worldwide_distributions' => [
                'bail', 'required', 'array', 'min:1'
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')
                    ->whereNull('deleted_at')
                    ->where('worldwide_quote_id', value(function () {
                        return $this->getQuote()->getKey();
                    }))
            ],
            'worldwide_distributions.*.pricing_document' => [
                'bail', 'required', 'string', 'max:1000'
            ],
            'worldwide_distributions.*.service_agreement_id' => [
                'bail', 'required', 'string', 'max:1000'
            ],
            'worldwide_distributions.*.system_handle' => [
                'bail', 'required', 'string', 'max:1000'
            ],
            'worldwide_distributions.*.purchase_order_number' => [
                'bail', 'required', 'string', 'max:250'
            ],
            'worldwide_distributions.*.vat_number' => [
                'bail', 'required', 'string', 'max:250'
            ],
            'worldwide_distributions.*.additional_details' => [
                'bail', 'nullable', 'string', 'max:10000'
            ],
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels())
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->quote ??= WorldwideQuote::whereHas('worldwideDistributions', function (Builder $builder) {
            $builder->whereKey(head($this->input('worldwide_distributions.*.id')));
        })->firstOrFail();
    }

    public function getStage(): ContractDetailsStage
    {
        return $this->stage ??= new ContractDetailsStage([
            'distributionDetailsCollection' => $this->getDistributionDetailsCollection(),
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage')),
        ]);
    }

    public function getDistributionDetailsCollection(): DistributionDetailsCollection
    {
        return $this->distributionDetailsCollection ??= with($this->input('worldwide_distributions'), function (array $detailsData) {
            /** @var Collection $distributions */
            $distributions = WorldwideDistribution::whereKey(Arr::pluck($detailsData, 'id'))->get()->keyBy('id');

            $collection = array_map(fn(array $distributionDetails) => new DistributionDetailsData([
                'worldwide_distribution' => $distributions[$distributionDetails['id']],
                'pricing_document' => $distributionDetails['pricing_document'],
                'service_agreement_id' => $distributionDetails['service_agreement_id'],
                'system_handle' => $distributionDetails['system_handle'],
                'purchase_order_number' => $distributionDetails['purchase_order_number'],
                'vat_number' => $distributionDetails['vat_number'],
                'additional_details' => $distributionDetails['additional_details'] ?? null
            ]), $detailsData);

            return new DistributionDetailsCollection($collection);
        });
    }
}

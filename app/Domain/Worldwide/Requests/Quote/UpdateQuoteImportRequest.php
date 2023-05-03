<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Address\Models\Address;
use App\Domain\Company\Models\Company;
use App\Domain\Contact\Models\Contact;
use App\Domain\Country\Models\Country;
use App\Domain\Currency\Models\Currency;
use App\Domain\Date\Enum\DateFormatEnum;
use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Vendor\Models\Vendor;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\DistributionExpiryDateCollection;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ImportStage;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class UpdateQuoteImportRequest extends FormRequest
{
    protected ?WorldwideQuote $worldwideQuote = null;

    protected ?ImportStage $importStage = null;

    protected ?DistributionExpiryDateCollection $distributionExpiryDateCollection = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'company_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Company::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at')->where('type', 'Internal'),
            ],
            'quote_currency_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'output_currency_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'quote_template_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(QuoteTemplate::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at'),
            ],
            'exchange_rate_margin' => [
                'bail', 'nullable', 'numeric', 'max:999',
            ],
            'quote_expiry_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],

            'are_end_user_addresses_available' => [
                'bail', 'boolean',
            ],

            'are_end_user_contacts_available' => [
                'bail', 'boolean',
            ],

            'worldwide_distributions' => [
                'bail', 'required', 'array',
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')
                    ->where('worldwide_quote_id', $this->getQuote()->active_version_id)->whereNull('deleted_at')->where(function (BaseBuilder $builder) {
                        $builder->whereExists(function (BaseBuilder $builder) {
                            $builder->selectRaw('1')
                                ->from('opportunity_suppliers')
                                ->whereColumn('worldwide_distributions.opportunity_supplier_id', 'opportunity_suppliers.id')
                                ->whereNull('opportunity_suppliers.deleted_at');
                        });
                    }),
            ],
            'worldwide_distributions.*.vendors' => [
                'bail', 'required', 'array', 'distinct',
            ],
            'worldwide_distributions.*.vendors.*' => [
                'bail', 'required', 'uuid',
                Rule::exists(Vendor::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at'),
            ],
            'worldwide_distributions.*.country_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Country::class, 'id')->whereNull('deleted_at')->whereNotNull('activated_at'),
            ],
            'worldwide_distributions.*.distribution_currency_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],
            'worldwide_distributions.*.distribution_currency_quote_currency_exchange_rate_margin' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'worldwide_distributions.*.distribution_currency_quote_currency_exchange_rate_value' => [
                'bail', 'nullable', 'numeric', 'min:0', 'max:999999',
            ],
            'worldwide_distributions.*.buy_currency_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],

            'worldwide_distributions.*.addresses' => [
                'bail', 'present', 'array',
            ],
            'worldwide_distributions.*.addresses.*' => [
                'bail', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at'),
            ],

            'worldwide_distributions.*.contacts' => [
                'bail', 'present', 'array',
            ],
            'worldwide_distributions.*.contacts.*' => [
                'bail', 'uuid',
                Rule::exists(Contact::class, 'id')->whereNull('deleted_at'),
            ],

            'worldwide_distributions.*.buy_price' => [
                'bail', 'required', 'numeric', 'min:0', 'max:999999999',
            ],
            'worldwide_distributions.*.calculate_list_price' => [
                'bail', 'nullable', 'boolean',
            ],
            'worldwide_distributions.*.distribution_expiry_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],
            'worldwide_distributions.*.file_date_format' => [
                'bail', 'nullable', new Enum(DateFormatEnum::class),
            ],

            'payment_terms' => [
                'bail', 'required', 'string', 'max:500',
            ],

            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels()),
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->worldwideQuote ??= $this->route('worldwide_quote');
    }

    public function getStage(): ImportStage
    {
        return $this->importStage ??= new ImportStage([
            'company_id' => $this->input('company_id'),
            'quote_currency_id' => $this->input('quote_currency_id'),
            'output_currency_id' => $this->input('output_currency_id'),
            'quote_template_id' => $this->input('quote_template_id'),
            'exchange_rate_margin' => transform($this->input('exchange_rate_margin'), fn ($value) => (float) $value),
            'quote_expiry_date' => Carbon::createFromFormat('Y-m-d', $this->input('quote_expiry_date')),
            'distributions_data' => array_map(fn (array $distributionData) => [
                'distribution_id' => $distributionData['id'],
                'vendors' => $distributionData['vendors'],
                'country_id' => $distributionData['country_id'],
                'distribution_currency_id' => $distributionData['distribution_currency_id'],
                'buy_currency_id' => $distributionData['buy_currency_id'],
                'distribution_currency_quote_currency_exchange_rate_margin' => transform($distributionData['distribution_currency_quote_currency_exchange_rate_margin'] ?? 0.0, fn ($value) => (float) $value),
                'distribution_currency_quote_currency_exchange_rate_value' => transform($distributionData['distribution_currency_quote_currency_exchange_rate_value'] ?? 1.0, fn ($value) => (float) $value),
                'buy_price' => (float) $distributionData['buy_price'],
                'calculate_list_price' => (bool) ($distributionData['calculate_list_price'] ?? false),
                'distribution_expiry_date' => Carbon::createFromFormat('Y-m-d', $distributionData['distribution_expiry_date']),
                'address_ids' => $distributionData['addresses'],
                'contact_ids' => $distributionData['contacts'],
                'file_date_format' => DateFormatEnum::from($distributionData['file_date_format'] ?? 'Auto'),
            ], $this->input('worldwide_distributions')),
            'payment_terms' => $this->input('payment_terms'),
            'are_end_user_addresses_available' => $this->boolean('are_end_user_addresses_available'),
            'are_end_user_contacts_available' => $this->boolean('are_end_user_contacts_available'),
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage')),
        ]);
    }
}

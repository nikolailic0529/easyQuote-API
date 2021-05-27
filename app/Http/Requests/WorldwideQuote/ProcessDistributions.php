<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\ProcessableDistribution;
use App\DTO\ProcessableDistributionCollection;
use App\Models\Address;
use App\Models\Contact;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Vendor;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

class ProcessDistributions extends FormRequest
{
    protected ?ProcessableDistributionCollection $distributionCollection = null;

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
            'worldwide_distributions.*.buy_currency_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(Currency::class, 'id'),
            ],

            'worldwide_distributions.*.addresses' => [
                'bail', 'required', 'array'
            ],
            'worldwide_distributions.*.addresses.*' => [
                'bail', 'uuid',
                Rule::exists(Address::class, 'id')->whereNull('deleted_at')
            ],

            'worldwide_distributions.*.contacts' => [
                'bail', 'required', 'array'
            ],
            'worldwide_distributions.*.contacts.*' => [
                'bail', 'uuid',
                Rule::exists(Contact::class, 'id')->whereNull('deleted_at')
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

            'worldwide_distributions.*.distributor_file_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(QuoteFile::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.distributor_file_page' => [
                'bail', 'nullable', 'int', 'min:1', 'max:999',
            ],

            'worldwide_distributions.*.schedule_file_id' => [
                'bail', 'nullable', 'uuid',
                Rule::exists(QuoteFile::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.schedule_file_page' => [
                'bail', 'nullable', 'int', 'min:1', 'max:999',
            ],
        ];
    }

    public function messages()
    {
        return [
            'worldwide_distributions.*.distribution_currency_id.required' => 'Currency is required in each distribution',
            'worldwide_distributions.*.distributor_file_id' => 'Distributor File is required in each distribution',
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

    public function getDistributionCollection(): ProcessableDistributionCollection
    {
        return $this->distributionCollection ??= with($this->input('worldwide_distributions'), function (array $distributions) {
            $collection = array_map(function (array $distribution) {
                return new ProcessableDistribution([
                    'id' => $distribution['id'],

                    'vendors' => $distribution['vendors'],
                    'country_id' => $distribution['country_id'],
                    'distribution_currency_id' => $distribution['distribution_currency_id'],
                    'buy_currency_id' => $distribution['buy_currency_id'],
                    'buy_price' => (float)$distribution['buy_price'],
                    'calculate_list_price' => (bool)($distribution['calculate_list_price'] ?? false),
                    'distribution_expiry_date' => Carbon::createFromFormat('Y-m-d', $distribution['distribution_expiry_date']),
                    'address_ids' => $distribution['addresses'],
                    'contact_ids' => $distribution['contacts'],

                    'distributor_file_id' => $distribution['distributor_file_id'],
                    'distributor_file_page' => (int)($distribution['distributor_file_page'] ?? null),
                    'schedule_file_id' => $distribution['schedule_file_id'] ?? null,
                    'schedule_file_page' => transform($distribution['schedule_file_page'] ?? null, fn (string $value) => (int)$value),
                ]);
            }, $distributions);

            return new ProcessableDistributionCollection($collection);
        });
    }

    public function formatErrorsBag(MessageBag $messageBag): string
    {
        $messages = $messageBag->setFormat(":key: :message")->all();

        return implode(PHP_EOL, $messages);
    }
}

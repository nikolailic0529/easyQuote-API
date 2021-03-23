<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\ProcessableDistribution;
use App\DTO\ProcessableDistributionCollection;
use App\Models\Data\Country;
use App\Models\Data\Currency;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\QuoteFile;
use App\Models\Vendor;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\Rule;

class ProcessDistributions extends FormRequest
{
    protected ?ProcessableDistributionCollection $distributionCollection = null;

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
                'bail', 'required', 'array',
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')->whereNull('deleted_at'),
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

            'worldwide_distributions.*.buy_price' => [
                'bail', 'required', 'numeric', 'min:0', 'max:999999999',
            ],

            'worldwide_distributions.*.calculate_list_price' => [
                'bail', 'nullable', 'boolean',
            ],

            'worldwide_distributions.*.distribution_expiry_date' => [
                'bail', 'required', 'date_format:Y-m-d',
            ],
        ];
    }

    public function messages()
    {
        return [
            'worldwide_distributions.*.vendors.required' => 'At least one vendor is required in each distribution',
            'worldwide_distributions.*.country_id.required' => 'Country is required in each distribution',
            'worldwide_distributions.*.quote_template_id.required' => 'Template is required in each distribution',
            'worldwide_distributions.*.distribution_currency_id.required' => 'Currency is required in each distribution',
            'worldwide_distributions.*.distributor_file_id' => 'Distributor File is required in each distribution',
            'worldwide_distributions.*.buy_price' => 'Buy price is required in each distribution',
        ];
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
                    'distributor_file_id' => $distribution['distributor_file_id'],
                    'distributor_file_page' => (int)($distribution['distributor_file_page'] ?? null),
                    'schedule_file_id' => $distribution['schedule_file_id'] ?? null,
                    'schedule_file_page' => $distribution['schedule_file_page'] ?? null,
                    'buy_price' => transform($distribution['buy_price'], fn($value) => (float)$value),
                    'calculate_list_price' => $distribution['calculate_list_price'] ?? false,
                    'distribution_expiry_date' => Carbon::createFromFormat('Y-m-d', $distribution['distribution_expiry_date']),
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

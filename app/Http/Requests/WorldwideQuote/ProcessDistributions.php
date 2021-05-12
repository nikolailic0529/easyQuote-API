<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\ProcessableDistribution;
use App\DTO\ProcessableDistributionCollection;
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
                    'distributor_file_id' => $distribution['distributor_file_id'],
                    'distributor_file_page' => (int)($distribution['distributor_file_page'] ?? null),
                    'schedule_file_id' => $distribution['schedule_file_id'] ?? null,
                    'schedule_file_page' => $distribution['schedule_file_page'] ?? null,
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

<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\SelectedDistributionRows;
use App\Domain\Worldwide\DataTransferObjects\DistributorQuote\SelectedDistributionRowsCollection;
use App\Domain\Worldwide\DataTransferObjects\QuoteStages\ReviewStage;
use App\Domain\Worldwide\Enum\ContractQuoteStage;
use App\Domain\Worldwide\Models\DistributionRowsGroup;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use App\Domain\Worldwide\Queries\WorldwideDistributionQueries;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SelectDistributionsRowsRequest extends FormRequest
{
    protected ?ReviewStage $reviewStage = null;

    protected ?SelectedDistributionRowsCollection $selectedDistributionRowsCollection = null;

    protected ?WorldwideQuote $worldwideQuoteModel = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(WorldwideDistributionQueries $queries)
    {
        return [
            'worldwide_distributions.*' => [
                'bail', 'required', 'array',
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.selected_rows' => [
                'bail', 'present', 'array',
            ],
            'worldwide_distributions.*.selected_rows.*' => [
                'bail', 'uuid',
            ],
            'worldwide_distributions.*.selected_groups' => [
                'bail', 'present', 'array',
            ],
            'worldwide_distributions.*.selected_groups.*' => [
                'bail', 'uuid',
            ],
            'worldwide_distributions.*.reject' => [
                'bail', 'required', 'boolean',
            ],
            'worldwide_distributions.*.sort_rows_column' => [
                'bail', 'nullable', Rule::in(['product_no', 'service_sku', 'description', 'serial_no', 'date_from', 'date_to', 'qty', 'price', 'pricing_document', 'system_handle', 'service_level_description', 'searchable']),
            ],
            'worldwide_distributions.*.sort_rows_direction' => [
                'bail', 'nullable', Rule::in(['asc', 'desc']),
            ],
            'worldwide_distributions.*.sort_rows_groups_column' => [
                'bail', 'nullable', Rule::in(['group_name', 'search_text', 'rows_count', 'rows_sum']),
            ],
            'worldwide_distributions.*.sort_rows_groups_direction' => [
                'bail', 'nullable', Rule::in(['asc', 'desc']),
            ],
            'worldwide_distributions.*.use_groups' => [
                'bail', 'required', 'boolean',
                function ($attribute, $value, \Closure $fail) use ($queries) {
                    if (!$value) {
                        return;
                    }

                    $distrKey = $this->input(Str::replaceLast('use_groups', 'id', $attribute));
                    $distrGroupsExist = DistributionRowsGroup::where('worldwide_distribution_id', $distrKey)->exists();

                    if ($distrGroupsExist) {
                        return;
                    }

                    $distrName = $queries->distributionQualifiedNameQuery($distrKey)->value('qualified_distribution_name') ?? $distrKey;

                    $fail("$distrName must contain one group at least to use grouping");
                },
            ],
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels()),
            ],
        ];
    }

    public function getQuote(): WorldwideQuote
    {
        return $this->worldwideQuoteModel ??= with(true, function (): WorldwideQuote {
            /** @var \App\Domain\Worldwide\Models\WorldwideQuoteVersion $version */
            $version = WorldwideQuoteVersion::query()->whereHas('worldwideDistributions', function (Builder $builder) {
                $builder->whereKey($this->input('worldwide_distributions.*.id'));
            })->sole();

            return $version->worldwideQuote;
        });
    }

    public function getStage(): ReviewStage
    {
        return $this->reviewStage ??= new ReviewStage([
            'selected_distribution_rows' => $this->getSelectedDistributionRowsCollection(),
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage')),
        ]);
    }

    public function getSelectedDistributionRowsCollection(): SelectedDistributionRowsCollection
    {
        return $this->selectedDistributionRowsCollection ??= with(true, function () {
            $collection = array_map(fn (array $distribution) => new SelectedDistributionRows([
                'worldwide_distribution_id' => $distribution['id'],
                'selected_rows' => $distribution['selected_rows'] ?? [],
                'selected_groups' => $distribution['selected_groups'] ?? [],
                'reject' => (bool) $distribution['reject'],
                'use_groups' => (bool) $distribution['use_groups'] ?? false,
                'sort_rows_column' => $distribution['sort_rows_column'] ?? null,
                'sort_rows_direction' => $distribution['sort_rows_direction'] ?? null,
                'sort_rows_groups_column' => $distribution['sort_rows_groups_column'] ?? null,
                'sort_rows_groups_direction' => $distribution['sort_rows_groups_direction'] ?? null,
            ]), $this->input('worldwide_distributions'));

            return new SelectedDistributionRowsCollection($collection);
        });
    }
}

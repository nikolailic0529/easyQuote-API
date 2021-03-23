<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\QuoteStages\ReviewStage;
use App\DTO\SelectedDistributionRows;
use App\DTO\SelectedDistributionRowsCollection;
use App\Enum\ContractQuoteStage;
use App\Models\Quote\WorldwideDistribution;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Queries\WorldwideDistributionQueries;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;
use Closure;

class SelectDistributionsRows extends FormRequest
{
    protected ?ReviewStage $reviewStage = null;

    protected ?SelectedDistributionRowsCollection $selectedDistributionRowsCollection = null;

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
    public function rules(WorldwideDistributionQueries $queries)
    {
        return [
            'worldwide_distributions.*' => [
                'bail', 'required', 'array'
            ],
            'worldwide_distributions.*.id' => [
                'bail', 'required', 'uuid',
                Rule::exists(WorldwideDistribution::class, 'id')->whereNull('deleted_at'),
            ],
            'worldwide_distributions.*.selected_rows' => [
                'bail', 'present', 'array',
            ],
            'worldwide_distributions.*.selected_rows.*' => [
                'bail', 'uuid'
            ],
            'worldwide_distributions.*.selected_groups' => [
                'bail', 'present', 'array'
            ],
            'worldwide_distributions.*.selected_groups.*' => [
                'bail', 'uuid'
            ],
            'worldwide_distributions.*.reject' => [
                'bail', 'required', 'boolean'
            ],
            'worldwide_distributions.*.sort_rows_column' => [
                'bail', 'nullable', Rule::in(['product_no', 'description', 'serial_no', 'date_from', 'date_to', 'qty', 'price', 'pricing_document', 'system_handle', 'service_level_description', 'searchable'])
            ],
            'worldwide_distributions.*.sort_rows_direction' => [
                'bail', 'nullable', Rule::in(['asc', 'desc'])
            ],
            'worldwide_distributions.*.sort_rows_groups_column' => [
                'bail', 'nullable', Rule::in(['group_name', 'search_text', 'rows_count', 'rows_sum'])
            ],
            'worldwide_distributions.*.sort_rows_groups_direction' => [
                'bail', 'nullable', Rule::in(['asc', 'desc'])
            ],
            'worldwide_distributions.*.use_groups' => [
                'bail', 'required', 'boolean',
                function ($attribute, $value, Closure $fail) use ($queries) {
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
                }
            ],
            'stage' => [
                'bail', 'required', Rule::in(ContractQuoteStage::getLabels())
            ],
        ];
    }

    public function getStage(): ReviewStage
    {
        return $this->reviewStage ??= new ReviewStage([
            'selected_distribution_rows' => $this->getSelectedDistributionRowsCollection(),
            'stage' => ContractQuoteStage::getValueOfLabel($this->input('stage'))
        ]);
    }

    public function getSelectedDistributionRowsCollection(): SelectedDistributionRowsCollection
    {
        return $this->selectedDistributionRowsCollection ??= with(true, function () {
            $collection = array_map(fn (array $distribution) => new SelectedDistributionRows([
                'worldwide_distribution_id'  => $distribution['id'],
                'selected_rows'              => $distribution['selected_rows'] ?? [],
                'selected_groups'            => $distribution['selected_groups'] ?? [],
                'reject'                     => (bool) $distribution['reject'],
                'use_groups'                 => (bool) $distribution['use_groups'] ?? false,
                'sort_rows_column'           => $distribution['sort_rows_column'] ?? null,
                'sort_rows_direction'        => $distribution['sort_rows_direction'] ?? null,
                'sort_rows_groups_column'    => $distribution['sort_rows_groups_column'] ?? null,
                'sort_rows_groups_direction' => $distribution['sort_rows_groups_direction'] ?? null,
            ]), $this->input('worldwide_distributions'));

            return new SelectedDistributionRowsCollection($collection);
        });
    }
}

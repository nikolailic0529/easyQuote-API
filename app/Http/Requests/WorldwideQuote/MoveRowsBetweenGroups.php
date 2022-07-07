<?php

namespace App\Http\Requests\WorldwideQuote;

use App\Http\Resources\V1\RowsGroup\RowsGroup;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveRowsBetweenGroups extends FormRequest
{
    protected ?DistributionRowsGroup $outputRowsGroup = null;

    protected ?DistributionRowsGroup $inputRowsGroup = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'output_rows_group_id' => [
                'bail', 'required', 'uuid',
                Rule::exists(DistributionRowsGroup::class, 'id')->where('worldwide_distribution_id', $this->route('worldwide_distribution')->getKey())
            ],
            'rows' => [
                'bail', 'required', 'array'
            ],
            'rows.*' => [
                'bail', 'required', 'uuid', 'distinct',
                Rule::exists('distribution_rows_group_mapped_row', 'mapped_row_id')->where('rows_group_id', $this->input('output_rows_group_id'))
            ],
            'input_rows_group_id' => [
                'bail', 'required', 'uuid', 'different:output_rows_group_id',
                Rule::exists(DistributionRowsGroup::class, 'id')->where('worldwide_distribution_id', $this->route('worldwide_distribution')->getKey())
            ]
        ];
    }

    public function messages()
    {
        return [
            'input_rows_group_id.different' => 'Output & Input Groups must be different.',
            'rows.*.exists' => 'The selected rows don\'t exist in the Output Group.'
        ];
    }

    public function getMovedRows(): array
    {
        return $this->input('rows');
    }

    public function getOutputRowsGroup(): DistributionRowsGroup
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->outputRowsGroup ??= DistributionRowsGroup::query()->findOrFail($this->input('output_rows_group_id'));
    }

    public function getInputRowsGroup(): DistributionRowsGroup
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->inputRowsGroup ??= DistributionRowsGroup::query()->findOrFail($this->input('input_rows_group_id'));
    }

    public function loadGroupAttributes(DistributionRowsGroup $group): DistributionRowsGroup
    {
        return tap($group, function (DistributionRowsGroup $group) {
            $group->loadMissing('rows')->loadCount('rows');

            $group->setAttribute('rows_sum', $group->rows()->sum('price'));

            /** @var WorldwideQuoteDataMapper $dataMapper */
            $dataMapper = $this->container[WorldwideQuoteDataMapper::class];

            $dataMapper->markExclusivityOfWorldwideDistributionRowsForCustomer($group->worldwideDistribution, $group->rows);
        });
    }

    public function getChangedRowsGroups(): array
    {
        $outputGroup = $this->loadGroupAttributes($this->getOutputRowsGroup());
        $inputGroup = $this->loadGroupAttributes($this->getInputRowsGroup());

        return [
            'output_rows_group' => RowsGroup::make($outputGroup),
            'input_rows_group' => RowsGroup::make($inputGroup)
        ];
    }
}

<?php

namespace App\Http\Requests\WorldwideQuote;

use App\DTO\RowsGroupData;
use App\Models\Quote\WorldwideDistribution;
use App\Models\QuoteFile\DistributionRowsGroup;
use App\Models\QuoteFile\MappedRow;
use App\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRowsGroup extends FormRequest
{
    protected ?RowsGroupData $rowsGroupData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'group_name' => [
                'bail', 'required', 'string', 'max:250',
                Rule::unique(DistributionRowsGroup::class, 'group_name')
                    ->ignore($this->getRowsGroup())
                    ->where('worldwide_distribution_id', $this->getWorldwideDistribution()->getKey())
            ],
            'search_text' => [
                'bail', 'required', 'string', 'max:250'
            ],
            'rows' => [
                'bail', 'required', 'array'
            ],
            'rows.*' => [
                'bail', 'required', 'uuid',
                Rule::exists(MappedRow::class, 'id')
            ]
        ];
    }

    public function getRowsGroup(): DistributionRowsGroup
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('rows_group');
    }

    public function getWorldwideDistribution(): WorldwideDistribution
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->route('worldwide_distribution');
    }

    public function getRowsGroupData(): RowsGroupData
    {
        return $this->rowsGroupData ??= new RowsGroupData([
            'group_name' => $this->input('group_name'),
            'search_text' => $this->input('search_text'),
            'rows' => $this->input('rows')
        ]);
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
}

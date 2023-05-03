<?php

namespace App\Domain\Worldwide\Requests\Opportunity;

use App\Domain\Worldwide\DataTransferObjects\Opportunity\BatchSaveOpportunitiesData;
use App\Domain\Worldwide\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;

class BatchSaveRequest extends FormRequest
{
    protected ?BatchSaveOpportunitiesData $batchSaveOpportunitiesData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'opportunities' => [
                'bail', 'required', 'array',
            ],
            'opportunities.*' => [
                'bail', 'uuid',
            ],
        ];
    }

    public function getBatchSaveData(): BatchSaveOpportunitiesData
    {
        return $this->batchSaveOpportunitiesData ??= new BatchSaveOpportunitiesData([
            'opportunities' => Opportunity::query()->whereKey($this->input('opportunities'))->withTrashed()->get()->all(),
        ]);
    }
}

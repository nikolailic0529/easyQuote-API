<?php

namespace App\Http\Requests\Opportunity;

use App\DTO\Opportunity\BatchSaveOpportunitiesData;
use App\Models\Opportunity;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BatchSave extends FormRequest
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
                'bail', 'required', 'array'
            ],
            'opportunities.*' => [
                'bail', 'uuid',
                Rule::exists(Opportunity::class, 'id')->whereNotNull('deleted_at')
            ]
        ];
    }

    public function getBatchSaveData(): BatchSaveOpportunitiesData
    {
        return $this->batchSaveOpportunitiesData ??= new BatchSaveOpportunitiesData([
            'opportunities' => Opportunity::query()->whereKey($this->input('opportunities'))->withTrashed()->get()->all()
        ]);
    }
}

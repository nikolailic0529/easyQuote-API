<?php

namespace App\Http\Requests\Opportunities;

use App\DTO\Opportunity\UploadOpportunityData;
use Illuminate\Foundation\Http\FormRequest;

class BatchUpload extends FormRequest
{
    protected ?UploadOpportunityData $importOpportunityData = null;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'opportunities_file' => [
                'bail', 'required', 'file', 'mimes:xlsx', 'max:10000'
            ],
            'accounts_data_file' => [
                'bail', 'nullable', 'file', 'mimes:xlsx', 'max:10000'
            ],
            'account_contacts_file' => [
                'bail', 'nullable', 'file', 'mimes:xlsx', 'max:10000'
            ]
        ];
    }

    public function getImportOpportunityData(): UploadOpportunityData
    {
        return $this->importOpportunityData ??= new UploadOpportunityData([
            'opportunities_file' => $this->file('opportunities_file'),
            'accounts_data_file' => $this->file('accounts_data_file'),
            'account_contacts_file' => $this->file('account_contacts_file'),
        ]);
    }
}

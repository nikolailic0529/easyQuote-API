<?php

namespace App\Domain\Worldwide\Requests\Opportunity;

use App\Domain\Note\DataTransferObjects\CreateNoteData;
use App\Domain\Note\DataTransferObjects\CreateOpportunityNoteData;
use Illuminate\Foundation\Http\FormRequest;

class CreateOpportunityNoteRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:20000'],
        ];
    }

    public function getCreateOpportunityNoteData(): CreateOpportunityNoteData
    {
        return new CreateOpportunityNoteData([
            'opportunity_id' => $this->route('opportunity')->getKey(),
            'text' => $this->input('text'),
        ]);
    }

    public function getCreateNoteData(): CreateNoteData
    {
        return CreateNoteData::from([
            'note' => $this->input('text'),
        ]);
    }
}

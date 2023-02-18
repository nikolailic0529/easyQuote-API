<?php

namespace App\Domain\Worldwide\Requests\Opportunity;

use App\Domain\Note\DataTransferObjects\UpdateNoteData;
use App\Domain\Note\DataTransferObjects\UpdateOpportunityNoteData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityNoteRequest extends FormRequest
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

    public function getUpdateOpportunityData(): UpdateOpportunityNoteData
    {
        return new UpdateOpportunityNoteData(['text' => $this->input('text')]);
    }

    public function getUpdateNoteData(): UpdateNoteData
    {
        return UpdateNoteData::from(['note' => $this->input('text')]);
    }
}

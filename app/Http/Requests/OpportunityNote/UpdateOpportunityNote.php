<?php

namespace App\Http\Requests\OpportunityNote;

use App\DTO\Note\UpdateNoteData;
use App\DTO\OpportunityNote\UpdateOpportunityNoteData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOpportunityNote extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
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
        return new UpdateNoteData(['note' => $this->input('text')]);
    }
}

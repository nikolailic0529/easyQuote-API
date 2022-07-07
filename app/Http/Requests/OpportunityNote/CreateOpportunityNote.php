<?php

namespace App\Http\Requests\OpportunityNote;

use App\DTO\Note\CreateNoteData;
use App\DTO\OpportunityNote\CreateOpportunityNoteData;
use Illuminate\Foundation\Http\FormRequest;

class CreateOpportunityNote extends FormRequest
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

    public function getCreateOpportunityNoteData(): CreateOpportunityNoteData
    {
        return new CreateOpportunityNoteData([
            'opportunity_id' => $this->route('opportunity')->getKey(),
            'text' => $this->input('text'),
        ]);
    }

    public function getCreateNoteData(): CreateNoteData
    {
        return new CreateNoteData([
            'note' => $this->input('text'),
        ]);
    }
}

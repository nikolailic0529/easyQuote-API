<?php

namespace App\Domain\Company\Requests;

use App\Domain\Note\DataTransferObjects\UpdateNoteData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyNote extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'text' => ['required', 'string', 'max:20000'],
        ];
    }

    public function getNoteText(): string
    {
        return $this->input('text');
    }

    public function getUpdateNoteData(): UpdateNoteData
    {
        return UpdateNoteData::from([
            'note' => $this->input('text'),
        ]);
    }
}

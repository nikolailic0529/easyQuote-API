<?php

namespace App\Http\Requests\CompanyNote;

use App\DTO\Note\CreateNoteData;
use Illuminate\Foundation\Http\FormRequest;

class CreateCompanyNote extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'text' => ['required', 'string', 'max:20000']
        ];
    }

    public function getNoteText(): string
    {
        return $this->input('text');
    }

    public function getCreateNoteData(): CreateNoteData
    {
        return new CreateNoteData([
            'note' => $this->input('text'),
        ]);
    }
}

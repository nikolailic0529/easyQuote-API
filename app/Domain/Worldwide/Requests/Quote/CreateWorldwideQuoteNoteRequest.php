<?php

namespace App\Domain\Worldwide\Requests\Quote;

use App\Domain\Note\DataTransferObjects\CreateNoteData;
use Illuminate\Foundation\Http\FormRequest;

class CreateWorldwideQuoteNoteRequest extends FormRequest
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

    public function getCreateNoteData(): CreateNoteData
    {
        return CreateNoteData::from([
            'note' => $this->input('text'),
        ]);
    }
}

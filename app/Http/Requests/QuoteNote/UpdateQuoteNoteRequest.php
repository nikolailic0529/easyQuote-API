<?php

namespace App\Http\Requests\QuoteNote;

use App\DTO\Note\UpdateNoteData;
use Illuminate\Foundation\Http\FormRequest;

class UpdateQuoteNoteRequest extends FormRequest
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

    public function getUpdateNoteData(): UpdateNoteData
    {
        return new UpdateNoteData([
            'note' => $this->input('text'),
        ]);
    }
}

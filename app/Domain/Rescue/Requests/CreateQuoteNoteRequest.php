<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Note\DataTransferObjects\CreateNoteData;
use Illuminate\Foundation\Http\FormRequest;

class CreateQuoteNoteRequest extends FormRequest
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

    public function validated()
    {
        return parent::validated() + [
            'quote_id' => optional($this->route('quote'))->id,
        ];
    }

    public function getCreateNoteData(): CreateNoteData
    {
        return CreateNoteData::from([
            'note' => $this->input('text'),
        ]);
    }
}

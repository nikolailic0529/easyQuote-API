<?php

namespace App\Http\Requests\Quote;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class StoreContractStateRequest extends FormRequest
{
    use HandlesAuthorization;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'closing_date' => 'nullable|date_format:Y-m-d',
            'additional_notes' => 'nullable|string|max:20000|min:2'
        ];
    }

    public function messages()
    {
        return [];
    }
}

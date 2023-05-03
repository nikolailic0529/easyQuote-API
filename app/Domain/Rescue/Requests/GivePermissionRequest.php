<?php

namespace App\Domain\Rescue\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GivePermissionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'users' => ['required', 'array'],
            'users.*' => ['string', 'uuid', Rule::exists('users', 'id')->whereNull('deleted_at')],
        ];
    }
}

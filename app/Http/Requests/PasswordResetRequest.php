<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasswordResetRequest extends FormRequest
{
    protected $appends = [];

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'password' => [
                'required',
                'string',
                'min:8',
                'confirmed'
            ]
        ];
    }

    public function append(array $data): void
    {
        $this->appends = array_merge($this->appends, $data);
    }

    public function validated()
    {
        $validated = parent::validated();

        return $validated + $this->appends;
    }
}

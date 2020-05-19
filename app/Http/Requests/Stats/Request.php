<?php

namespace App\Http\Requests\Stats;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }

    /**
     * Resolve user id when user does not have super role.
     *
     * @return string|null
     */
    public function userId(): ?string
    {
        return optional($this->user())->hasRole(R_SUPER) ? null : optional($this->user())->getKey();
    }
}

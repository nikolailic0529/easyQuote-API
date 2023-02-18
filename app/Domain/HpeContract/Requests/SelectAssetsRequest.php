<?php

namespace App\Domain\HpeContract\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class SelectAssetsRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'ids.*' => 'integer',
            'reject' => 'boolean',
        ];
    }

    public function getIds()
    {
        return Collection::wrap($this->input('ids'))->toArray();
    }
}

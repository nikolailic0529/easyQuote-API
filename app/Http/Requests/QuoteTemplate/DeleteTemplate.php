<?php

namespace App\Http\Requests\QuoteTemplate;

use Illuminate\Foundation\Http\FormRequest;

class DeleteTemplate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\QuoteTemplate\BaseQuoteTemplate */
        $template = head($this->route()->parameters());

        error_abort_if($template->isAttached(), QTAD_01, 'QTAD_01', 409);

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
            //
        ];
    }
}

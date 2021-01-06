<?php

namespace App\Http\Requests\QuoteTemplate;

use App\Models\Template\QuoteTemplate;
use App\Services\QuoteTemplateQueries;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeleteTemplate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\Template\QuoteTemplate */
        $template = head($this->route()->parameters());

        if (!$template instanceof QuoteTemplate) {
            return true;
        }

        if ((new QuoteTemplateQueries)->referencedQuery($template->getKey())->exists()) {
            throw new UnprocessableEntityHttpException("You can't delete the Quote Template used in Quotes or Quote Versions.");
        }

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

<?php

namespace App\Domain\Template\Requests\QuoteTemplate;

use App\Domain\Rescue\Models\QuoteTemplate;
use App\Domain\Template\Queries\QuoteTemplateQueries;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeleteTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(QuoteTemplateQueries $queries)
    {
        /** @var \App\Domain\Rescue\Models\QuoteTemplate */
        $template = head($this->route()->parameters());

        if (!$template instanceof QuoteTemplate) {
            return true;
        }

        if ($queries->referencedQuery($template->getKey())->exists()) {
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
        ];
    }
}

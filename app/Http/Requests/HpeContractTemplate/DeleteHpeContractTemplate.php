<?php

namespace App\Http\Requests\HpeContractTemplate;

use App\Models\Template\HpeContractTemplate;
use App\Services\HpeContractTemplateQueries;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeleteHpeContractTemplate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\Template\HpeContractTemplate */
        $template = head($this->route()->parameters());

        if (!$template instanceof HpeContractTemplate) {
            return true;
        }

        if ((new HpeContractTemplateQueries)->referencedQuery($template->getKey())->exists()) {
            throw new UnprocessableEntityHttpException("You can't delete the HPE Contract Template used in HPE Contracts.");
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

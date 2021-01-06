<?php

namespace App\Http\Requests\ContractTemplate;

use App\Models\Template\ContractTemplate;
use App\Services\ContractTemplateQueries;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeleteContractTemplate extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        /** @var \App\Models\Template\ContractTemplate */
        $template = head($this->route()->parameters());

        if (!$template instanceof ContractTemplate) {
            return true;
        }

        if ((new ContractTemplateQueries)->referencedQuery($template->getKey())->exists()) {
            throw new UnprocessableEntityHttpException("You can't delete the Contract Template used in Contracts.");
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

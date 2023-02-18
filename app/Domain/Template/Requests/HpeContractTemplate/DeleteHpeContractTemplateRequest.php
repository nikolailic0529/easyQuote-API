<?php

namespace App\Domain\Template\Requests\HpeContractTemplate;

use App\Domain\HpeContract\Models\HpeContractTemplate;
use App\Domain\HpeContract\Queries\HpeContractTemplateQueries;
use Illuminate\Foundation\Http\FormRequest;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DeleteHpeContractTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(HpeContractTemplateQueries $queries)
    {
        /** @var \App\Domain\HpeContract\Models\HpeContractTemplate */
        $template = head($this->route()->parameters());

        if (!$template instanceof HpeContractTemplate) {
            return true;
        }

        if ($queries->referencedQuery($template->getKey())->exists()) {
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
        ];
    }
}

<?php

namespace App\Domain\Company\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCompanyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        /** @var \App\Domain\Company\Models\Company $company */
        $company = $this->route('company');

        if ($company->opportunities()->exists()) {
            return Response::deny("You can not delete the company, it's attached to one or more opportunities.");
        }

        return Response::allow();
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

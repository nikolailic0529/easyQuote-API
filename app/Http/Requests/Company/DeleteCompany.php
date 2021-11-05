<?php

namespace App\Http\Requests\Company;

use App\Models\Company;
use Illuminate\Auth\Access\Response;
use Illuminate\Foundation\Http\FormRequest;

class DeleteCompany extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return Response
     */
    public function authorize(): Response
    {
        /** @var Company $company */
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
            //
        ];
    }
}

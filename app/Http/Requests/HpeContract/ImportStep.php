<?php

namespace App\Http\Requests\HpeContract;

use App\Contracts\Repositories\CompanyRepositoryInterface as Companies;
use Illuminate\Foundation\Http\FormRequest;

class ImportStep extends FormRequest
{   
    protected Companies $companies;

    public function __construct(Companies $companies)
    {
        $this->companies = $companies;
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

    public function getData(): array
    {
        return [
            'companies' => $this->companies->allInternalWithCountries(['id', 'name'])
        ];
    }
}

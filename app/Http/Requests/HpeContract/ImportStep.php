<?php

namespace App\Http\Requests\HpeContract;

use App\Queries\CompanyQueries;
use App\Services\ProfileHelper;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

class ImportStep extends FormRequest
{
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

    #[ArrayShape(['companies' => "\Illuminate\Database\Eloquent\Collection"])]
    public function getData(): array
    {
        /** @var CompanyQueries $companyQueries */
        $companyQueries = $this->container[CompanyQueries::class];

        $companies = $companyQueries->listOfInternalCompaniesWithCountries()
            ->whereKey(ProfileHelper::profileCompaniesIds())
            ->with('image')
            ->get()
            ->makeHidden('image')
            ->append('logo')
            ->values();

        return [
            'companies' => $companies,
        ];
    }
}

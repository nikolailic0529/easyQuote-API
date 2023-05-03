<?php

namespace App\Domain\HpeContract\Requests;

use App\Domain\Company\Queries\CompanyQueries;
use App\Domain\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

class ImportStepRequest extends FormRequest
{
    public function rules(): array
    {
        return [
        ];
    }

    #[ArrayShape(['companies' => "\Illuminate\Database\Eloquent\Collection"])]
    public function getData(): array
    {
        /** @var CompanyQueries $companyQueries */
        $companyQueries = $this->container[CompanyQueries::class];

        /** @var User $user */
        $user = $this->user();

        $companies = $companyQueries->listOfInternalCompaniesWithCountries()
            ->whereKey($user->companies->modelKeys())
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

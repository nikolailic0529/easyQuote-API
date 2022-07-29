<?php

namespace App\Http\Requests\Company;

use App\Enum\CompanyCategoryEnum;
use App\Enum\CompanySource;
use App\Enum\CompanyType;
use App\Queries\VendorQueries;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

class ShowCompanyFormData extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
        ];
    }

    #[ArrayShape(['types' => "array", 'categories' => "array", 'sources' => "array", 'vendors' => "\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection"])]
    public function getFormData(): array
    {
        /** @var VendorQueries $vendorQueries */
        $vendorQueries = $this->container[VendorQueries::class];

        return [
            'types' => CompanyType::getValues(),
            'categories' => $this->resolveCategories(),
            'sources' => CompanySource::getValues(),
            'vendors' => $vendorQueries->listOfActiveVendorsQuery()->get(),
        ];
    }

    private function resolveCategories(): array
    {
        $categories = collect(CompanyCategoryEnum::cases());

        if (config('request-correlation.update-customer-from-opportunity') === $this->input('correlation_id')) {
            $categories = $categories->reject(CompanyCategoryEnum::EndUser);
        }

        return $categories->values()->all();
    }
}

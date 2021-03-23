<?php

namespace App\Http\Requests\Quote;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Contracts\Repositories\QuoteFile\DataSelectSeparatorRepositoryInterface;
use App\Facades\Setting;
use App\Models\Company;
use App\Services\ProfileHelper;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;

class FirstStep extends FormRequest
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

    public function data(): array
    {
        /** @var CompanyRepositoryInterface $companies */
        $companies = app(CompanyRepositoryInterface::class);

        /** @var DataSelectSeparatorRepositoryInterface $dataSelects */
        $dataSelects = app(DataSelectSeparatorRepositoryInterface::class);

        /** @var CurrencyRepositoryInterface $currencies */
        $currencies = app(CurrencyRepositoryInterface::class);

        /** @var \App\Models\User $user */
        $user = $this->user();

        $filteredCompanies = $companies->allInternalWithVendorsAndCountries()
            ->unless($user->hasRole(R_SUPER), fn(Collection $collection) => $collection->find(ProfileHelper::profileCompaniesIds()))
            ->when($this->has('prioritize.company'), function (Collection $collection) {
                return $collection->prioritize(fn(Company $company) => $company->short_code === $this->input('prioritize.company'));
            }, function (Collection $collection) {
                return $collection->prioritize(fn(Company $company) => $company->getKey() === ProfileHelper::defaultCompanyId());
            })
            ->loadMissing('image')
            ->makeHidden('image')
            ->append('logo')
            ->values();

        return [
            'companies' => $filteredCompanies,
            'data_select_separators' => $dataSelects->all(),
            'supported_file_types' => Setting::get('supported_file_types_ui'),
            'currencies' => $currencies->allHaveExrate()
        ];
    }
}

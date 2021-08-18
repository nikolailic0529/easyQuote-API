<?php

namespace App\Http\Requests\Quote;

use App\Contracts\Repositories\CurrencyRepositoryInterface;
use App\Facades\Setting;
use App\Models\Company;
use App\Models\QuoteFile\DataSelectSeparator;
use App\Models\User;
use App\Queries\CompanyQueries;
use App\Services\ProfileHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;

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

    #[ArrayShape(['companies' => "\App\Models\Company[]|\Illuminate\Database\Eloquent\Collection", 'data_select_separators' => "\App\Models\QuoteFile\DataSelectSeparator[]|\Illuminate\Database\Eloquent\Collection", 'supported_file_types' => "mixed", 'currencies' => "mixed"])]
    public function data(): array
    {
        /** @var CompanyQueries $companyQueries */
        $companyQueries = $this->container[CompanyQueries::class];

        /** @var CurrencyRepositoryInterface $currencies */
        $currencies = app(CurrencyRepositoryInterface::class);

        /** @var User $user */
        $user = $this->user();

        /** @var Collection|Company[] $filteredCompanies */
        $filteredCompanies = $companyQueries->listOfInternalCompaniesQuery()
            ->with([
                'vendors' => function (Relation $relation) {
                    $relation->whereNotNull($relation->qualifyColumn('activated_at'));
                },
                'vendors.countries.defaultCurrency',
            ])
            ->unless($user->hasRole(R_SUPER), function (Builder $builder) {
                $builder->whereKey(ProfileHelper::profileCompaniesIds());
            })
            ->get()
            ->each(function (Company $company) {
                $company->prioritizeDefaultCountryOnVendors();
            })
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
            'data_select_separators' => DataSelectSeparator::all(),
            'supported_file_types' => Setting::get('supported_file_types_ui'),
            'currencies' => $currencies->allHaveExrate(),
        ];
    }
}

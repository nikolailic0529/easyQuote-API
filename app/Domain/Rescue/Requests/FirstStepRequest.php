<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Company\Models\Company;
use App\Domain\Company\Queries\CompanyQueries;
use App\Domain\Currency\Contracts\CurrencyRepositoryInterface;
use App\Domain\QuoteFile\Models\DataSelectSeparator;
use App\Domain\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Http\FormRequest;
use JetBrains\PhpStorm\ArrayShape;
use function App\Foundation\Support\Collection\prioritize;

class FirstStepRequest extends FormRequest
{
    public function rules(): array
    {
        return [
        ];
    }

    #[ArrayShape(['companies' => "\App\Models\Company[]|\Illuminate\Database\Eloquent\Collection", 'data_select_separators' => "\App\Models\QuoteFile\DataSelectSeparator[]|\Illuminate\Database\Eloquent\Collection", 'supported_file_types' => 'mixed', 'currencies' => 'mixed'])]
    public function data(): array
    {
        /** @var CompanyQueries $companyQueries */
        $companyQueries = $this->container[CompanyQueries::class];

        /** @var CurrencyRepositoryInterface $currencies */
        $currencies = app(CurrencyRepositoryInterface::class);

        /** @var User $user */
        $user = $this->user();

        $companiesQuery = $companyQueries->listOfInternalCompaniesQuery();

        /** @var Collection|Company[] $filteredCompanies */
        $filteredCompanies = $companiesQuery
            ->select([
                $companiesQuery->getModel()->getQualifiedKeyName(),
                $companiesQuery->qualifyColumn('name'),
                $companiesQuery->qualifyColumn('short_code'),
                $companiesQuery->qualifyColumn('default_country_id'),
                $companiesQuery->qualifyColumn('default_vendor_id'),
                $companiesQuery->qualifyColumn('default_template_id'),
            ])
            ->with([
                'vendors' => static function (Relation $relation): void {
                    $relation->whereNotNull($relation->qualifyColumn('activated_at'));
                },
                'vendors.countries.defaultCurrency',
            ])
            ->unless($user->hasRole(R_SUPER), function (Builder $builder) use ($user): void {
                $builder->whereKey($user->companies->modelKeys());
            })
            ->get()
            ->each(static function (Company $company): void {
                $company->prioritizeDefaultCountryOnVendors();
            })
            ->when(
                value: $this->has('prioritize.company'),
                callback: function (Collection $collection): Collection {
                    return prioritize($collection, function (Company $company): bool {
                        return $company->short_code === $this->input('prioritize.company');
                    });
                },
                default: function (Collection $collection) use ($user): Collection {
                    if (!$user->company()->getParentKey()) {
                        return $collection;
                    }

                    return prioritize($collection, function (Company $company) use ($user): bool {
                        return $company->getKey() === $user->company()->getParentKey();
                    });
                }
            )
            ->loadMissing('image')
            ->makeHidden('image')
            ->append('logo')
            ->values();

        return [
            'companies' => $filteredCompanies,
            'data_select_separators' => DataSelectSeparator::all(),
            'supported_file_types' => \setting('supported_file_types_ui'),
            'currencies' => $currencies->allHaveExrate(),
        ];
    }
}

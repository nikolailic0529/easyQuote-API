<?php

namespace App\Domain\Company\Services;

use App\Domain\Company\Enum\CompanySource;
use App\Domain\Company\Models\Company;
use App\Domain\SalesUnit\Models\SalesUnit;
use App\Foundation\Database\Eloquent\QueryFilter\DataTransferObjects\Enum\FilterTypeEnum;
use App\Foundation\Database\Eloquent\QueryFilter\DataTransferObjects\FilterData;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class CompanyQueryFilterDataProvider
{
    public function __construct(
        protected readonly Gate $gate,
    ) {
    }

    public function getFilters(Request $request): DataCollection
    {
        $collection = tap(collect(), function (Collection $collection) use ($request): void {
            /** @var \App\Domain\User\Models\User $user */
            $user = $request->user();

            $units = $this->gate->allows('viewAnyOwnerEntities', Company::class)
                ? SalesUnit::query()->get()
                : $user->salesUnits->merge($user->salesUnitsFromLedTeams);

            if ($units->count() > 1) {
                $collection->push(
                    FilterData::from([
                        'label' => __('Sales Unit'),
                        'type' => FilterTypeEnum::Multiselect,
                        'parameter' => 'filter[sales_unit_id]',
                        'possible_values' => $units
                            ->lazy()
                            ->sortBy('unit_name', SORT_NATURAL)
                            ->map(static function (SalesUnit $unit): array {
                                return [
                                    'label' => $unit->unit_name,
                                    'value' => $unit->getKey(),
                                ];
                            })
                            ->values()
                            ->all(),
                    ])
                );
            }

            $collection->push(
                FilterData::from([
                    'label' => __('Source'),
                    'type' => FilterTypeEnum::Multiselect,
                    'parameter' => 'filter[source]',
                    'possible_values' => [
                        ['label' => 'System 4', 'value' => CompanySource::S4],
                        ['label' => 'easyQuote', 'value' => CompanySource::EQ],
                        ['label' => 'Pipeliner', 'value' => CompanySource::PL],
                    ],
                ])
            );

            $collection->push(
                FilterData::from([
                    'label' => __('Customer Name'),
                    'type' => FilterTypeEnum::Textbox,
                    'parameter' => 'filter[customer_name]',
                    'possible_values' => [],
                ])
            );
        });

        return (new DataCollection(FilterData::class, $collection))->wrap('data');
    }
}

<?php

namespace App\Services\Opportunity;

use App\DTO\QueryFilter\Enum\FilterTypeEnum;
use App\DTO\QueryFilter\FilterData;
use App\Models\Opportunity;
use App\Models\SalesUnit;
use App\Models\User;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataCollection;

class OpportunityQueryFilterDataProvider
{
    public function __construct(
        protected readonly Gate $gate,
    ) {
    }

    public function getFilters(Request $request): DataCollection
    {
        $collection = tap(collect(), function (Collection $collection) use ($request): void {
            /** @var User $user */
            $user = $request->user();

            $units = $this->gate->allows('viewAnyOwnerEntities', Opportunity::class)
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

            $opportunityModel = new Opportunity();
            $userModel = new User();

            $collection->push(
                FilterData::from([
                    'label' => __('Account Manager'),
                    'type' => FilterTypeEnum::Multiselect,
                    'parameter' => 'filter[account_manager_id]',
                    'possible_values' => $userModel->newQuery()
                        ->select([
                            $userModel->getQualifiedKeyName(),
                            $userModel->qualifyColumn('user_fullname'),
                        ])
                        ->join(
                            $opportunityModel->getTable(),
                            $opportunityModel->accountManager()->getQualifiedForeignKeyName(),
                            $userModel->getQualifiedKeyName()
                        )
                        ->groupBy($user->getQualifiedKeyName())
                        ->get()
                        ->sortBy('user_fullname', SORT_NATURAL)
                        ->values()
                        ->map(static function (User $user): array {
                            return [
                                'label' => $user->user_fullname,
                                'value' => $user->getKey(),
                            ];
                        }),
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

            $collection->push(
                FilterData::from([
                    'label' => __('Include Archived'),
                    'type' => FilterTypeEnum::Checkbox,
                    'parameter' => 'include_archived',
                    'possible_values' => [],
                ])
            );

            $collection->push(
                FilterData::from([
                    'label' => __('Only Archived'),
                    'type' => FilterTypeEnum::Checkbox,
                    'parameter' => 'only_archived',
                    'possible_values' => [],
                ])
            );
        });

        return (new DataCollection(FilterData::class, $collection))->wrap('data');
    }
}
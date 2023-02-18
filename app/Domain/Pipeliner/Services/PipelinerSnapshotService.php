<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\Integration\GraphQl\PipelinerAccountIntegration;
use App\Domain\Pipeliner\Integration\GraphQl\PipelinerOpportunityIntegration;
use App\Domain\Pipeliner\Integration\Models\AccountFilterInput;
use App\Domain\Pipeliner\Integration\Models\EntityFilterStringField;
use App\Domain\Pipeliner\Integration\Models\OpportunityFilterInput;
use App\Domain\Pipeliner\Integration\Models\SalesUnitFilterInput;
use App\Domain\Pipeliner\Models\PipelinerSnapshot;
use App\Domain\Pipeliner\Models\PipelinerSnapshotEntry;
use App\Domain\SalesUnit\Models\SalesUnit;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\LazyCollection;

class PipelinerSnapshotService
{
    public function __construct(
        protected readonly PipelinerOpportunityIntegration $opportunityIntegration,
        protected readonly PipelinerAccountIntegration $accountIntegration,
    ) {
    }

    public function create(callable $onProgress = null): PipelinerSnapshot
    {
        $onProgress ??= static function (): void {
        };

        $snapshot = tap(new PipelinerSnapshot(), static function (PipelinerSnapshot $snapshot): void {
            $snapshot->save();
        });

        foreach ($this->iterateData() as $item) {
            $this->createSnapshotEntry($snapshot, $item);

            $onProgress();
        }

        return $snapshot;
    }

    protected function createSnapshotEntry(PipelinerSnapshot $snapshot, array $item): PipelinerSnapshotEntry
    {
        return tap(new PipelinerSnapshotEntry(),
            function (PipelinerSnapshotEntry $entry) use ($snapshot, $item): void {
                $entry->snapshot()->associate($snapshot);
                $entry->reference = Arr::pull($item, '__reference');
                $entry->type = Arr::pull($item, '__type');
                $entry->data = $item;

                $entry->save();
            });
    }

    protected function iterateData(): \Generator
    {
        $salesUnits = $this->getEnabledSalesUnits();
        $unitNames = $salesUnits->pluck('unit_name');

        yield from LazyCollection::make(function () use ($unitNames): \Generator {
            yield from $this->opportunityIntegration->rawScroll(
                filter: OpportunityFilterInput::new()
                    ->unit(SalesUnitFilterInput::new()->name(EntityFilterStringField::eq(...$unitNames))),
                first: 100
            );
        })
            ->values()
            ->map(static function (array $item): array {
                $item['__type'] = 'opportunity';
                $item['__reference'] = $item['id'];

                return $item;
            });

        yield from LazyCollection::make(function () use ($unitNames): \Generator {
            yield from $this->accountIntegration->rawScroll(
                filter: AccountFilterInput::new()
                    ->unit(SalesUnitFilterInput::new()->name(EntityFilterStringField::eq(...$unitNames))),
                first: 100
            );
        })
            ->values()
            ->map(static function (array $item): array {
                $item['__type'] = 'account';
                $item['__reference'] = $item['id'];

                return $item;
            });
    }

    protected function getEnabledSalesUnits(): Collection
    {
        return SalesUnit::query()
            ->where('is_enabled', true)
            ->get();
    }
}

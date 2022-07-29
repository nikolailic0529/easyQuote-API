<?php

namespace App\Services\SalesUnit;

use App\DTO\SalesUnit\CreateOrUpdateSalesUnitData;
use App\DTO\SalesUnit\CreateOrUpdateSalesUnitDataCollection;
use App\Models\SalesUnit;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SalesUnitEntityService
{
    public function __construct(protected ConnectionResolverInterface $connectionResolver,
                                protected ValidatorInterface          $validator)
    {
    }

    public function bulkCreateOrUpdateSalesUnits(CreateOrUpdateSalesUnitDataCollection $data): Collection
    {
        foreach ($data as $item) {
            $violations = $this->validator->validate($item);

            if (count($violations)) {
                throw new ValidationFailedException($data, $violations);
            }
        }

        $data->rewind();

        $modelKeys = LazyCollection::make($data)
            ->pluck('id')
            ->reject(static fn(?string $value): bool => is_null($value))
            ->mapWithKeys(static function (string $value): array {
                return [$value => true];
            })
            ->all();

        /** @var Collection $units */
        /** @var Collection $toBeDeletedUnits */
        [$units, $toBeDeletedUnits] = SalesUnit::query()->get()
            ->partition(static function (SalesUnit $unit) use ($modelKeys): bool {
                return isset($modelKeys[$unit->getKey()]);
            });

        $unitMap = $units->getDictionary();

        /** @var Collection $models */
        $models = LazyCollection::make($data)
            ->map(static function (CreateOrUpdateSalesUnitData $item) use ($unitMap) {
                $model = is_null($item->id) ? new SalesUnit() : $unitMap[$item->id];

                return tap($model, static function (SalesUnit $unit) use ($item): void {
                    $unit->forceFill($item->except('id')->toArray());
                });
            })
            ->values()
            ->pipe(static function (LazyCollection $collection): Collection {
                return Collection::make($collection->all());
            });

        $this->connectionResolver->connection()
            ->transaction(static function () use ($models, $toBeDeletedUnits) {
                $toBeDeletedUnits->each->delete();

                SalesUnit::query()->whereKey($models->modelKeys())->update(['is_default' => false]);

                $models->each->save();
            });

        return $models;
    }
}
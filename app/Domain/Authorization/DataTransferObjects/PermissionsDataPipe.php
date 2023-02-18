<?php

namespace App\Domain\Authorization\DataTransferObjects;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\LaravelData\DataPipes\DataPipe;
use Spatie\LaravelData\Support\DataClass;

final class PermissionsDataPipe implements DataPipe
{
    public function __construct(protected Gate $gate)
    {
    }

    public function handle(mixed $payload, DataClass $class, Collection $properties): Collection
    {
        if (!$payload instanceof Model) {
            return $properties;
        }

        $abilities = ['update', 'delete'];

        $properties['permissions'] = PermissionsData::from(
            collect($abilities)->mapWithKeys(function (string $ability) use ($payload): array {
                return [$ability => $this->gate->allows($ability, $payload)];
            })
        );

        return $properties;
    }
}

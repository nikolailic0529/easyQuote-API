<?php

namespace App\Domain\Space\Services;

use App\Domain\Space\DataTransferObjects\PutSpaceDataCollection;
use App\Domain\Space\Models\Space;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class SpaceEntityService
{
    protected ConnectionInterface $connection;
    protected ValidatorInterface $validator;

    public function __construct(ConnectionInterface $connection, ValidatorInterface $validator)
    {
        $this->connection = $connection;
        $this->validator = $validator;
    }

    public function batchPutSpaces(PutSpaceDataCollection $collection): Collection
    {
        foreach ($collection as $data) {
            $violations = $this->validator->validate($data);

            if (count($violations)) {
                throw new ValidationFailedException($data, $violations);
            }
        }

        $collection->rewind();

        $spaceModels = [];

        foreach ($collection as $data) {
            $spaceModels[] = tap(new Space(), function (Space $space) use ($data) {
                $space->{$space->getKeyName()} = $data->space_id ?? (string) Uuid::generate(4);

                if (!is_null($data->space_id)) {
                    $space->exists = true;
                }

                $space->space_name = $data->space_name;
            });
        }

        return tap(new Collection($spaceModels), function (Collection $spaceCollection) {
            $spaceModelKeys = $spaceCollection->modelKeys();

            $this->connection->transaction(function () use ($spaceCollection, $spaceModelKeys) {
                Space::query()->whereKeyNot($spaceModelKeys)->delete();

                foreach ($spaceCollection as $spaceModel) {
                    $spaceModel->save();
                }
            });
        });
    }
}

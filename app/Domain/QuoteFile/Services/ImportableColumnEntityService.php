<?php

namespace App\Domain\QuoteFile\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\ImportableColumn\DataTransferObjects\CreateColumnData;
use App\Domain\ImportableColumn\DataTransferObjects\UpdateColumnData;
use App\Domain\ImportableColumn\DataTransferObjects\UpdateOrCreateColumnData;
use App\Domain\QuoteFile\Events\ImportableColumn\ImportableColumnCreated;
use App\Domain\QuoteFile\Events\ImportableColumn\ImportableColumnDeleted;
use App\Domain\QuoteFile\Events\ImportableColumn\ImportableColumnUpdated;
use App\Domain\QuoteFile\Models\ImportableColumn;
use App\Domain\QuoteFile\Models\ImportableColumnAlias;
use App\Domain\Sync\Enum\Lock;
use App\Domain\User\Models\{User};
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Webpatser\Uuid\Uuid;

class ImportableColumnEntityService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(protected ConnectionInterface $connection,
                                protected LockProvider $lockProvider,
                                protected ValidatorInterface $validator,
                                protected EventDispatcher $eventDispatcher)
    {
    }

    public function createColumn(CreateColumnData $data): ImportableColumn
    {
        $violations = $this->validator->validate($data);

        count($violations) && throw new ValidationFailedException($data, $violations);

        return tap(new ImportableColumn(), function (ImportableColumn $column) use ($data) {
            $column->{$column->getKeyName()} = $data->id ?? (string) Uuid::generate(4);

            $column->header = $data->header;
            $column->de_header_reference = $data->de_header_reference;
            $column->name = $data->name ?? Str::slug($data->header, '_');
            $column->type = $data->type;
            $column->country()->associate($data->country_id);

            if ($this->causer instanceof User) {
                $column->user()->associate($this->causer);
            }

            $column->order = $data->order;
            $column->is_system = $data->is_system;
            $column->is_temp = $data->is_temp;
            $column->activated_at = now();

            $this->lockProvider->lock(Lock::CREATE_IMPORTABLE_COLUMN, 10)
                ->block(30, function () use ($column, $data) {
                    $this->connection->transaction(function () use ($column) {
                        $column->saveQuietly();
                    });

                    if (false === empty($data->aliases)) {
                        $this->createAliasesForColumn($column, ...$data->aliases);
                    }

                    $this->eventDispatcher->dispatch(
                        new ImportableColumnCreated(
                            importableColumn: $column->load('aliases'),
                            causer: $this->causer,
                        )
                    );
                });
        });
    }

    public function updateColumn(ImportableColumn $column, UpdateColumnData $data): ImportableColumn
    {
        return tap($column, function (ImportableColumn $column) use ($data) {
            $column->load('aliases');

            $original = tap($column->newFromBuilder($column->getRawOriginal()), function (ImportableColumn $original) use ($column) {
                $original->setRelation('aliases', $column->aliases);
            });

            if (false === is_null($data->de_header_reference)) {
                $column->de_header_reference = $data->de_header_reference;
            }

            $column->header = $data->header;
            $column->name = $data->name ?? $column->name;
            $column->type = $data->type;
            $column->country()->associate($data->country_id);
            $column->order = $data->order;
            $column->is_system = $data->is_system;
            $column->is_temp = $data->is_temp;

            $this->lockProvider->lock(Lock::UPDATE_IMPORTABLE_COLUMN($column->getKey()), 10)
                ->block(30, function () use ($column, $data) {
                    $this->connection->transaction(function () use ($column) {
                        $column->saveQuietly();
                    });

                    $this->connection->transaction(function () use ($data, $column) {
                        $column->aliases()->whereNotIn('alias', $data->aliases)->delete();
                    });

                    $newAliasNames = array_values(array_diff($data->aliases, $column->aliases->pluck('alias')->all()));

                    if (false === empty($newAliasNames)) {
                        $this->createAliasesForColumn($column, ...$newAliasNames);
                    }
                });

            $this->eventDispatcher->dispatch(
                new ImportableColumnUpdated(
                    importableColumn: $column->load('aliases'),
                    originalImportableColumn: $original,
                    causer: $this->causer,
                )
            );
        });
    }

    public function deleteColumn(ImportableColumn $column): void
    {
        $this->lockProvider->lock(Lock::DELETE_IMPORTABLE_COLUMN($column->getKey()), 10)
            ->block(30, function () use ($column) {
                $this->connection->transaction(function () use ($column) {
                    $column->delete();
                });
            });

        $this->eventDispatcher->dispatch(
            new ImportableColumnDeleted(
                importableColumn: $column,
                causer: $this->causer,
            )
        );
    }

    public function markColumnAsActive(ImportableColumn $column): void
    {
        $original = $column->newFromBuilder($column->getRawOriginal());

        $column->activated_at = now();

        $this->connection->transaction(function () use ($column) {
            $column->saveQuietly();
        });

        $this->eventDispatcher->dispatch(
            new ImportableColumnUpdated(
                importableColumn: $column,
                originalImportableColumn: $original,
                causer: $this->causer,
            )
        );
    }

    public function markColumnAsInactive(ImportableColumn $column): void
    {
        $original = $column->newFromBuilder($column->getRawOriginal());

        $column->activated_at = null;

        $this->connection->transaction(function () use ($column) {
            $column->saveQuietly();
        });

        $this->eventDispatcher->dispatch(
            new ImportableColumnUpdated(
                importableColumn: $column,
                originalImportableColumn: $original,
                causer: $this->causer,
            )
        );
    }

    public function updateOrCreateColumn(ImportableColumn $column, UpdateOrCreateColumnData $data): ImportableColumn
    {
        $violations = $this->validator->validate($data);

        count($violations) && throw new ValidationFailedException($data, $violations);

        return tap($column, function () use ($column, $data) {
            if (false === $column->exists) {
                $column->{$column->getKeyName()} = $data->id ?? (string) Uuid::generate(4);
            }
            $column->name = $data->name;
            $column->header = $data->header;
            $column->de_header_reference = $data->de_header_reference;
            $column->type = $data->type;
            $column->country()->associate($data->country_id);
            $column->order = $data->order;
            $column->is_system = $data->is_system;
            $column->is_temp = $data->is_temp;

            $this->connection->transaction(function () use ($column) {
                $column->saveQuietly();
            });

            $aliasNames = array_values(array_unique($data->aliases));

            $existingAliasNames = $column->aliases()->pluck('alias')->all();

            $missingAliasNames = array_values(array_diff($aliasNames, $existingAliasNames));

            $this->createAliasesForColumn($column, ...$missingAliasNames);

            $this->pruneAliasDuplicatesOfColumn($column);
        });
    }

    public function createAliasesForColumn(ImportableColumn $column, string ...$aliases): void
    {
        foreach ($aliases as $aliasName) {
            tap(new ImportableColumnAlias(), function (ImportableColumnAlias $alias) use ($column, $aliasName) {
                $alias->{$alias->getKeyName()} = (string) Uuid::generate(4);
                $alias->alias = $aliasName;
                $alias->importableColumn()->associate($column);

                $this->connection->transaction(function () use ($alias) {
                    $alias->saveQuietly();
                });
            });
        }
    }

    public function pruneAliasDuplicatesOfColumn(ImportableColumn $column): void
    {
        $aliases = $column->aliases()->get()->toBase();

        $duplicates = $aliases->duplicates('alias');

        $duplicatedIds = $aliases
            ->filter(fn ($alias, $key) => $duplicates->get($key, false))
            ->pluck('id');

        if ($duplicatedIds->isEmpty()) {
            return;
        }

        $this->connection->transaction(fn () => $column->aliases()->whereKey($duplicatedIds)->forceDelete());
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, function () use ($causer) {
            $this->causer = $causer;
        });
    }
}

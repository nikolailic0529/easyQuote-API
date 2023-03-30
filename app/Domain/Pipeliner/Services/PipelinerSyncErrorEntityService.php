<?php

namespace App\Domain\Pipeliner\Services;

use App\Domain\Pipeliner\DataTransferObjects\BatchArchiveSyncErrorData;
use App\Domain\Pipeliner\DataTransferObjects\BatchRestoreSyncErrorData;
use App\Domain\Pipeliner\Models\PipelinerSyncError;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class PipelinerSyncErrorEntityService
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver,
        protected readonly LockProvider $lockProvider,
    ) {
    }

    public function ensureSyncErrorCreatedForMessage(
        Model $model,
        string $strategy,
        string $message
    ): PipelinerSyncError {
        return $this->lockProvider->lock($this->getLockKeyFor($model->getKey()), 10)
            ->block(30, function () use ($model, $message, $strategy) {
                $error = \App\Domain\Pipeliner\Models\PipelinerSyncError::query()
                    ->whereNull('resolved_at')
                    ->where('strategy_name', $strategy)
                    ->whereMorphedTo('entity', $model)
                    ->where('error_message_hash', sha1($message))
                    ->first();

                if ($error !== null) {
                    return $error;
                }

                return tap(new \App\Domain\Pipeliner\Models\PipelinerSyncError(),
                    function (PipelinerSyncError $error) use ($strategy, $message, $model) {
                        $error->entity()->associate($model);
                        $error->error_message = $message;
                        $error->strategy_name = $strategy;

                        $this->connectionResolver->connection()
                            ->transaction(static fn () => $error->save());
                    });
            });
    }

    public function createSyncErrorFor(
        Model $model,
        string $strategy,
        string $message
    ): PipelinerSyncError {
        return $this->lockProvider->lock($this->getLockKeyFor($model->getKey()), 10)
            ->block(30, function () use ($strategy, $message, $model): \App\Domain\Pipeliner\Models\PipelinerSyncError {
                return tap(new \App\Domain\Pipeliner\Models\PipelinerSyncError(),
                    function (PipelinerSyncError $error) use ($strategy, $message, $model) {
                        $error->entity()->associate($model);
                        $error->error_message = $message;
                        $error->strategy_name = $strategy;

                        $this->connectionResolver->connection()
                            ->transaction(static fn () => $error->save());
                    });
            });
    }

    public function markRelatedSyncErrorsResolved(Model $model, string $strategy): void
    {
        \App\Domain\Pipeliner\Models\PipelinerSyncError::query()
            ->whereNull('resolved_at')
            ->whereMorphedTo('entity', $model)
            ->where('strategy_name', $strategy)
            ->lazyById()
            ->each(function (PipelinerSyncError $error): void {
                $this->markSyncErrorResolved($error);
            });
    }

    public function markSyncErrorResolved(PipelinerSyncError $error): void
    {
        $error->resolved_at = now();

        $this->lockProvider->lock($this->getLockKeyFor($error->entity()->getParentKey()), 10)
            ->block(30, function () use ($error) {
                $this->connectionResolver->connection()
                    ->transaction(static function () use ($error): void {
                        $error->save();
                    });
            });
    }

    public function markSyncErrorArchived(PipelinerSyncError $error): void
    {
        $error->archived_at = now();

        $this->lockProvider->lock($this->getLockKeyFor($error->entity()->getParentKey()), 10)
            ->block(30, function () use ($error) {
                $this->connectionResolver->connection()
                    ->transaction(static function () use ($error): void {
                        $error->save();
                    });
            });
    }

    public function batchMarkSyncErrorArchived(BatchArchiveSyncErrorData $data): void
    {
        \App\Domain\Pipeliner\Models\PipelinerSyncError::query()
            ->whereKey($data->syncErrors->toCollection()->pluck('id'))
            ->lazyById()
            ->each(function (PipelinerSyncError $error) {
                $this->markSyncErrorArchived($error);
            });
    }

    public function markAllSyncErrorsArchived(): void
    {
        \App\Domain\Pipeliner\Models\PipelinerSyncError::query()
            ->whereNull('archived_at')
            ->whereNull('resolved_at')
            ->lazyById()
            ->each(function (PipelinerSyncError $error): void {
                $this->markSyncErrorArchived($error);
            });
    }

    public function markAllSyncErrorNotArchived(): void
    {
        \App\Domain\Pipeliner\Models\PipelinerSyncError::query()
            ->whereNotNull('archived_at')
            ->whereNull('resolved_at')
            ->lazyById()
            ->each(function (PipelinerSyncError $error): void {
                $this->markSyncErrorNotArchived($error);
            });
    }

    public function markSyncErrorNotArchived(PipelinerSyncError $error): void
    {
        $error->archived_at = null;

        $this->lockProvider->lock($this->getLockKeyFor($error->entity()->getParentKey()), 10)
            ->block(30, function () use ($error) {
                $this->connectionResolver->connection()
                    ->transaction(static function () use ($error): void {
                        $error->save();
                    });
            });
    }

    public function batchMarkSyncErrorNotArchived(BatchRestoreSyncErrorData $data): void
    {
        \App\Domain\Pipeliner\Models\PipelinerSyncError::query()
            ->whereKey($data->syncErrors->toCollection()->pluck('id'))
            ->lazyById()
            ->each(function (PipelinerSyncError $error) {
                $this->markSyncErrorNotArchived($error);
            });
    }

    protected function getLockKeyFor(string $related): string
    {
        return static::class.$related;
    }
}

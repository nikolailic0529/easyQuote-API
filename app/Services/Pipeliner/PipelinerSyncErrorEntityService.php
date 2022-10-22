<?php

namespace App\Services\Pipeliner;

use App\Models\Pipeliner\PipelinerSyncError;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Eloquent\Model;

class PipelinerSyncErrorEntityService
{
    public function __construct(
        protected readonly ConnectionResolverInterface $connectionResolver
    ) {
    }

    public function createSyncErrorFor(Model $model, string $message): PipelinerSyncError
    {
        return tap(new PipelinerSyncError(), function (PipelinerSyncError $error) use ($message, $model) {
            $error->entity()->associate($model);
            $error->error_message = $message;

            $this->connectionResolver->connection()
                ->transaction(static fn() => $error->save());
        });
    }

    public function archiveSyncError(PipelinerSyncError $error): void
    {
        $error->archived_at = now();

        $this->connectionResolver->connection()
            ->transaction(static function () use ($error): void {
                $error->save();
            });
    }
}
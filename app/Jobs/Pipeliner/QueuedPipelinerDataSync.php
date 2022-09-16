<?php

namespace App\Jobs\Pipeliner;

use App\Console\Commands\SyncPipelinerData;
use App\Events\Pipeliner\QueuedPipelinerSyncFailed;
use App\Events\Pipeliner\QueuedPipelinerSyncProcessed;
use App\Models\User;
use App\Services\Pipeliner\SyncPipelinerDataStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class QueuedPipelinerDataSync implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public int $timeout = 60 * 60 * 8;
    public int $tries = 1;
    public string $queue = 'long';
    public string $connection = 'redis_long';

    public function __construct(protected ?Model $causer = null,
                                protected array  $strategies = [])
    {
    }

    public function handle(Kernel $kernel): void
    {
        $parameters = [
            '--quiet' => true,
            '--strategy' => $this->strategies,
        ];

        if ($this->causer instanceof User) {
            $parameters['--user-id'] = $this->causer->getKey();
        }

        $kernel->call(SyncPipelinerData::class, $parameters);
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);

        app(SyncPipelinerDataStatus::class)->clear();

        if ($this->causer instanceof User) {
            event(new QueuedPipelinerSyncFailed($exception, $this->causer));
        }
    }
}

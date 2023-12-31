<?php

namespace App\Domain\Pipeliner\Jobs;

use App\Domain\Pipeliner\Events\AggregateSyncFailed;
use App\Domain\Pipeliner\Services\PipelinerDataSyncService;
use App\Domain\Pipeliner\Services\PipelinerSyncAggregate;
use App\Domain\Pipeliner\Services\SyncPipelinerDataStatus;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Log\LogManager;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class QueuedPipelinerDataSync implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable;
    use InteractsWithQueue;
    use SerializesModels;

    public int $timeout = 60 * 60 * 8;
    public int $tries = 1;
    public string $queue = 'long';
    public string $connection = 'redis_long';

    protected string $owner;

    public function __construct(
        protected readonly string $aggregateId,
        protected ?Model $causer = null,
        protected array $strategies = [],
        string $owner = null
    ) {
        $this->owner = $owner ?? Str::random();
    }

    public function handle(
        SyncPipelinerDataStatus $status,
        PipelinerSyncAggregate $syncAggregate,
        PipelinerDataSyncService $service,
        LogManager $logManager,
    ): void {
        $status->setOwner($this->owner);

        $syncAggregate->withId($this->aggregateId);

        try {
            $service
                ->setLogger($logManager->channel('pipeliner'))
                ->setCauser($this->causer)
                ->setStrategyFilter($this->resolveStrategyFilter())
                ->sync();
        } finally {
            $status->release();
        }
    }

    protected function resolveStrategyFilter(): callable
    {
        $strategies = $this->strategies;

        if (empty($strategies)) {
            $map = config('pipeliner.sync.strategies');

            $strategies = collect(config('pipeliner.sync.default_strategies'))
                ->map(static function (string $name) use ($map): string {
                    return $map[$name];
                })
                ->all();
        }

        return static function (object $strategy) use ($strategies) {
            foreach ($strategies as $classname) {
                if ($strategy instanceof $classname) {
                    return true;
                }
            }

            return false;
        };
    }

    public function failed(\Throwable $exception): void
    {
        report($exception);

        app(SyncPipelinerDataStatus::class)
            ->setOwner($this->owner)
            ->release();

        event(new AggregateSyncFailed($this->aggregateId, $exception));
    }
}

<?php

namespace App\Foundation\Support\Elasticsearch\Services;

use App\Domain\Authentication\Contracts\CauserAware;
use App\Foundation\Log\Contracts\LoggerAware;
use App\Foundation\Support\Elasticsearch\Events\SearchRebuildCompleted;
use App\Foundation\Support\Elasticsearch\Jobs\RebuildSearch;
use App\Foundation\Support\Elasticsearch\Services\Exceptions\QueueRebuildSearchException;
use Illuminate\Contracts\Bus\QueueingDispatcher as BusDispatcher;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\Eloquent\Model;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RebuildSearchQueueService implements CauserAware, LoggerAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly Config $config,
        protected readonly BusDispatcher $busDispatcher,
        protected readonly LockProvider $lockProvider,
        protected LoggerInterface $logger = new NullLogger()
    ) {
    }

    /**
     * @throws QueueRebuildSearchException
     */
    public function queueSearchRebuild(): void
    {
        if (method_exists($this->logger, 'withContext')) {
            $this->logger->withContext([
                'causer_id' => $this->causer?->getKey(),
            ]);
        }

        $this->logger->info('Search rebuild: queueing...');

        $lockName = static::class;

        $lockAcquired = $this->lockProvider->lock($lockName, 60 * 10)->get();

        if (!$lockAcquired) {
            $this->logger->warning('Search rebuild: in queue already.');

            throw new QueueRebuildSearchException('Rebuild search is queued already.');
        }

        $job = new RebuildSearch(
            models: $this->config->get('elasticsearch.reindex_models', []),
            causer: $this->causer,
        );

        $causer = $this->causer;

        $job->chain([
            static function (LockProvider $lockProvider, EventDispatcher $events) use ($lockName, $causer): void {
                $lockProvider->lock($lockName)->forceRelease();

                $events->dispatch(new SearchRebuildCompleted($causer));
            },
        ]);

        $job->onQueue('search-index');

        $this->busDispatcher->dispatch($job);

        $this->logger->info('Search rebuild: queued.');
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn () => $this->logger = $logger);
    }
}

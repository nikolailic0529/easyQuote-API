<?php

namespace App\Services\Elasticsearch;

use App\Contracts\CauserAware;
use App\Contracts\LoggerAware;
use App\Jobs\Search\RebuildSearch;
use App\Services\Elasticsearch\Exceptions\QueueRebuildSearchException;
use Illuminate\Contracts\Bus\QueueingDispatcher as BusDispatcher;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as Config;
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

            throw new QueueRebuildSearchException("Rebuild search is queued already.");
        }

        $job = new RebuildSearch(
            models: $this->config->get('elasticsearch.reindex_models', []),
            causer: $this->causer,
        );

        $job->chain([
            static function (LockProvider $lockProvider) use ($lockName): void {
                $lockProvider->lock($lockName)->forceRelease();
            }
        ]);

        $this->busDispatcher->dispatch($job);

        $this->logger->info('Search rebuild: queued.');
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn() => $this->causer = $causer);
    }

    public function setLogger(LoggerInterface $logger): static
    {
        return tap($this, fn() => $this->logger = $logger);
    }
}
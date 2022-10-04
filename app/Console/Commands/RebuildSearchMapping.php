<?php

namespace App\Console\Commands;

use App\Contracts\HasReindexQuery;
use App\Contracts\SearchableEntity;
use Carbon\Carbon;
use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use GuzzleHttp\RetryMiddleware;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Console\Input\InputOption;

class RebuildSearchMapping extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'eq:search-reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform indexing of defined entities on Elasticsearch';

    /**
     * Execute the console command.
     *
     * @param ElasticsearchClient $elasticsearch
     * @return int
     */
    public function handle(ElasticsearchClient $elasticsearch): int
    {
        if ($this->getLaravel()->runningUnitTests() && true !== $this->option('force')) {
            $this->info('Running testing environment. Indexing won\'t be proceeded.');

            return self::SUCCESS;
        }

        try {
            retry(
                times: 15,
                callback: function () use ($elasticsearch): bool {
                    $this->line('Elasticsearch: ping...');
                    return $elasticsearch->ping();
                },
                sleepMilliseconds: static fn (int $retries): int => (int) pow(2, $retries - 1) * 1000,
                when: static fn (\Throwable $e): bool => $e instanceof NoNodesAvailableException
            );

            $this->info('Elasticsearch: alive');

            $start = Carbon::now()->toImmutable();

            $models = $this->option('model');

            $indicesToDelete = empty($models) ? ['_all'] : array_map([self::class, 'getSearchIndexOfModel'], $models);
            $indexModels = empty($models) ? config('elasticsearch.reindex_models') : $models;

            foreach ($indicesToDelete as $index) {
                $this->line(sprintf("Deleting of '%s' index...", $index));

                try {
                    $elasticsearch->indices()->delete(['index' => $index]);
                } catch (Missing404Exception) {
                }

                $this->info(sprintf("The entries associated with '%s' index have been deleted.", $index));
            }

            $this->newLine();

            $this->handleModels(...$indexModels);

            $elapsedTime = now()->diffInMilliseconds($start);
            $indicesCount = $elasticsearch->count(['index' => '_all'])['count'] ?? 0;

            $this->comment(sprintf("<options=bold>%-15s</> <fg=green;options=bold>$indicesCount</>", "Total indices:"));
            $this->comment(sprintf("<options=bold>%-15s</> <fg=yellow;options=bold>$elapsedTime ms</>", "Elapsed time:"));

            return self::FAILURE;
        } catch (NoNodesAvailableException $e) {
            $this->error("Elasticsearch is either not configured properly or stopped.");

            return self::INVALID;
        }
    }

    private function handleModels(string ...$models)
    {
        /** @var ElasticsearchClient $elasticsearch */
        $elasticsearch = $this->laravel[ElasticsearchClient::class];

        foreach ($models as $model) {
            /** @var Builder $query */
            [$model, $query] = static::modelQuery($model);

            $plural = class_basename($model);

            $this->comment("Indexing of $plural entities...");

            $this->output->progressStart($query->count());

            $query->chunk($this->option('batch-size'), function (Collection $chunk) use ($elasticsearch) {
                $params = ['body' => []];

                foreach ($chunk as $entry) {
                    /** @var SearchableEntity $entry */

                    $params['body'][] = [
                        'index' => [
                            '_index' => $entry->getSearchIndex(),
                            '_type' => $entry->getSearchIndex(),
                            '_id' => $entry->getKey(),
                        ],
                    ];

                    $params['body'][] = $entry->toSearchArray();
                }

                $elasticsearch->bulk($params);

                $this->output->progressAdvance($chunk->count());
            });

            $this->output->progressFinish();
        }
    }

    private static function getSearchIndexOfModel(string $model): string
    {
        $model = '\\'.ltrim($model, '\\');

        if (!class_exists($model)) {
            throw new \InvalidArgumentException("Class $model does not exist.");
        }

        if (!is_subclass_of($model, SearchableEntity::class)) {
            throw new \InvalidArgumentException(sprintf('Class %s must implement %s.', $model, SearchableEntity::class));
        }

        return (new $model)->getSearchIndex();
    }

    private static function modelQuery(Builder|string $model): array
    {
        if ($model instanceof Builder) {
            return [$model->getModel(), $model];
        }

        if (false === class_exists($model)) {
            throw new \InvalidArgumentException("Class $model does not exist.");
        }

        $model = new $model;

        if (false === $model instanceof Model) {
            throw new \InvalidArgumentException(sprintf('Class %s must be an instance of %s.', $model, Model::class));
        }

        if ($model instanceof HasReindexQuery) {
            return [$model, $model::reindexQuery()];
        }

        return [$model, $model->query()];
    }

    protected function getOptions(): array
    {
        return [
            ['--batch-size', null, InputOption::VALUE_OPTIONAL, 'Index batch size', 2000],
            ['--force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in testing'],
            ['--model', 'm', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Specify the models to process indexing'],
        ];
    }
}

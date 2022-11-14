<?php

namespace App\Services\Elasticsearch;

use App\Contracts\HasReindexQuery;
use App\Contracts\SearchableEntity;
use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class IndexService
{
    protected int $batchSize = 2_000;

    public function __construct(
        protected readonly Client $client
    ) {
    }

    public function bulkBuildModelIndices(iterable $classes, callable $onProgress = null): void
    {
        $onProgress ??= static function (int $count): void {
        };

        $params = ['body' => []];
        $currentBatchSize = 0;

        $this->client->indices()->delete(['index' => '*']);

        foreach ($classes as $class) {
            foreach ($this->mapModelBulkIndexParams($class) as $pair) {
                $params['body'] = [...$params['body'], ...$pair];

                $currentBatchSize++;

                if ($currentBatchSize % $this->batchSize === 0) {
                    $this->client->bulk($params);

                    $onProgress($currentBatchSize);

                    $params['body'] = [];
                    $currentBatchSize = 0;
                }
            }
        }

        if ($currentBatchSize > 0) {
            $this->client->bulk($params);
            $onProgress($currentBatchSize);
        }
    }

    protected function mapModelBulkIndexParams(string $class): iterable
    {
        $model = static::resolveModel($class);
        $query = static::resolveQuery($model);

        return $query
            ->lazyById()
            ->map(static function (SearchableEntity $entry): array {
                return [
                    // Action
                    [
                        'index' => [
                            '_index' => $entry->getSearchIndex(),
                            '_type' => $entry->getSearchIndex(),
                            '_id' => $entry->getKey(),
                        ],
                    ],
                    // Metadata
                    $entry->toSearchArray(),
                ];
            });
    }

    private static function resolveModel(string $class): Model&SearchableEntity
    {
        if (class_exists($class) === false) {
            throw new InvalidArgumentException("Class [$class] does not exist.");
        }

        if (is_a($class, Model::class, true) === false) {
            throw new InvalidArgumentException(
                sprintf("Class [%s] must be an instance of [%s].", $class, Model::class)
            );
        }

        if (is_a($class, SearchableEntity::class, true) === false) {
            throw new InvalidArgumentException(
                sprintf("Class must implement [%s] interface.", SearchableEntity::class)
            );
        }

        return new $class;
    }

    /**
     * @param  Model  $model
     * @return Builder
     */
    private static function resolveQuery(Model $model): Builder
    {
        if ($model instanceof HasReindexQuery) {
            return $model::reindexQuery();
        }

        return $model::query();
    }

    /**
     * @param  class-string<Model&SearchableEntity>  $class
     * @param  callable|null  $onStart
     * @param  callable|null  $onProgress
     * @return void
     */
    public function buildModelIndices(string $class, callable $onStart = null, callable $onProgress = null): void
    {
        $onProgress ??= static function (int $count): void {
        };

        $model = static::resolveModel($class);
        $query = static::resolveQuery($model);

        if (is_callable($onStart)) {
            $onStart($query->count());
        }

        $client = $this->client;

        $this->deleteModelIndices($class);

        $query->chunk($this->batchSize, static function (Collection $chunk) use ($client, $onProgress): void {
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

            $client->bulk($params);

            $onProgress($chunk->count());
        });
    }

    public function deleteModelIndices(string $class): void
    {
        $model = static::resolveModel($class);

        try {
            $this->client->indices()->delete(['index' => $model->getSearchIndex()]);
        } catch (Missing404Exception) {
        }
    }

    public function setBatchSize(int $size): static
    {
        return tap($this, fn() => $this->batchSize = $size);
    }

}
<?php

namespace App\Console\Commands;

use App\Contracts\ReindexQuery;
use App\Contracts\SearchableEntity;
use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;


class ReindexCommand extends Command
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
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @param ElasticsearchClient $elasticsearch
     * @return int
     */
    public function handle(ElasticsearchClient $elasticsearch): int
    {
        $start = now();

        if ($this->getLaravel()->runningUnitTests() && true !== $this->option('force')) {
            $this->info('Running testing environment. Indexing won\'t be proceeded.');

            return 0;
        }

        try {
            $models = $this->option('model');

            $indicesToDelete = empty($models) ? ['_all'] : array_map([self::class, 'getSearchIndexOfModel'], $models);
            $indexModels = empty($models) ? config('elasticsearch.reindex_models') : $models;

            foreach ($indicesToDelete as $index) {
                $this->line(sprintf("Deleting of '%s' index...", $index));

                $elasticsearch->indices()->delete(['index' => $index]);

                $this->info(sprintf("The entries associated with '%s' index have been deleted.", $index));
            }

            $this->line('');

            $this->handleModels($indexModels);

            $elapsedTime = now()->diffInMilliseconds($start);
            $indicesCount = $elasticsearch->count(['index' => '_all'])['count'] ?? 0;

            $this->comment("<options=bold>Total indices:</>  <fg=green;options=bold>$indicesCount</>");
            $this->comment("<options=bold>Elapsed time:</>   $elapsedTime ms");

            return 0;
        } catch (NoNodesAvailableException $e) {
            $this->error("Elasticsearch is either not configured properly or stopped.");

            return 2;
        }
    }

    private function handleModels(array $models)
    {
        /** @var ElasticsearchClient */
        $elasticsearch = app(ElasticsearchClient::class);

        foreach ($models as $model) {
            [$model, $query] = static::modelQuery($model);

            $plural = Str::plural(class_basename($model));

            $this->comment("Indexing all {$plural}...");

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
                        ]
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

    private static function modelQuery($model): array
    {
        if ($model instanceof Builder) {
            return [$model->getModel(), $model];
        }

        if (is_a($model, ReindexQuery::class, true)) {
            return [new $model, $model::reindexQuery()];
        }

        return [$model = (new $model), $model->query()];
    }

    protected function getOptions(): array
    {
        return [
            ['--batch-size', null, InputOption::VALUE_OPTIONAL, 'Index batch size', 2000],
            ['--force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in testing'],
            ['--model', 'm', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL, 'Specify the models to process indexing']
        ];
    }
}

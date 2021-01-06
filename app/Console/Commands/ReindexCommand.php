<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Illuminate\Console\Command;
use App\Contracts\ReindexQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;


class ReindexCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:search-reindex';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Indexes all entries to Elasticsearch';

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
     * @return mixed
     */
    public function handle(ElasticsearchClient $elasticsearch)
    {
        $start = now();

        if (app()->runningUnitTests()) {
            $this->info('Running testing environment. Reindexing is skipped.');

            return 0;
        }
        
        // Perform deleting on all indices.
        $this->info("Deleting all indexes...");

        try {
            $elasticsearch->indices()->delete(['index' => '_all']);
        } catch (NoNodesAvailableException $e) {
            $this->error($e->getMessage());
            $this->error("Reindexing is skipped.");

            return 2;
        }

        $this->handleModels(config('elasticsearch.reindex_models'));

        $elapsedTime = now()->diffInMilliseconds($start);
        $indiciesCount = $elasticsearch->count(['index' => '_all'])['count'] ?? 0;

        $this->comment("<options=bold>Indicies:</>  <fg=green;options=bold>$indiciesCount</>");
        $this->comment("<options=bold>Time:</>      $elapsedTime ms");

        return 0;
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

            $query->chunk(1000, function (Collection $chunk) use ($elasticsearch) {
                $params = ['body' => []];

                foreach ($chunk as $entry) {
                    $params['body'][] = [
                        'index' => [
                            '_index' => $entry->getSearchIndex(),
                            '_type' => $entry->getSearchType(),
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
}

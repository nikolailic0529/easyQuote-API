<?php

namespace App\Console\Commands;

use App\Services\Elasticsearch\IndexService;
use App\Services\Elasticsearch\PingService;
use Carbon\Carbon;
use Elasticsearch\Common\Exceptions\NoNodesAvailableException;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputOption;

class RebuildSearchCommand extends Command
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
     * @param  IndexService  $indexService
     * @param  PingService  $pingService
     * @return int
     */
    public function handle(IndexService $indexService, PingService $pingService): int
    {
        if ($this->getLaravel()->runningUnitTests() && !$this->option('force')) {
            $this->info('Running testing environment. Indexing won\'t be proceeded.');

            return self::SUCCESS;
        }

        try {
            $pingService->waitUntilAvailable(onPing: function (): void {
                $this->line('Elasticsearch: ping...');
            });

            $this->info('Elasticsearch: alive');

            $start = Carbon::now()->toImmutable();

            $models = $this->option('model');

            $models = empty($models) ? config('elasticsearch.reindex_models') : $models;

            $this->newLine();

            $this->buildIndices(service: $indexService, models: $models);

            $elapsedTime = now()->diffInMilliseconds($start);

            $this->comment(sprintf("<options=bold>%-10s</> <fg=yellow;options=bold>$elapsedTime ms</>",
                "Elapsed time:"));

            return self::FAILURE;
        } catch (NoNodesAvailableException) {
            $this->error("Elasticsearch is either not configured properly or stopped.");

            return self::INVALID;
        }
    }

    private function buildIndices(IndexService $service, iterable $models): void
    {
        ProgressBar::setFormatDefinition('minimal_nomax', "Indexing: %current%");

        $bar = $this->output->createProgressBar();

        $bar->setFormat('minimal_nomax');

        $bar->start();

        $service
            ->setBatchSize((int) $this->option('batch-size'))
            ->bulkBuildModelIndices($models, onProgress: static function (int $count) use ($bar): void {
                $bar->advance($count);
            });

        $bar->finish();

        $this->newLine(2);
    }

    protected function getOptions(): array
    {
        return [
            ['--batch-size', null, InputOption::VALUE_OPTIONAL, 'Index batch size', 2000],
            ['--force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in testing'],
            [
                '--model', 'm', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Specify the models to process indexing',
            ],
        ];
    }
}

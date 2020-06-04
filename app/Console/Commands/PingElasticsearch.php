<?php

namespace App\Console\Commands;

use Elasticsearch\Client;
use Illuminate\Console\Command;
use Symfony\Polyfill\Php72\Php72;
use Throwable;

class PingElasticsearch extends Command
{
    protected const SLEEP_ON_RETRY = 2000; // 2000 ms

    protected const RETRY_TIMES = 5;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:ping-es';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ping Elasticseach and restart if it requires';

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
    public function handle(Client $elasticsearch)
    {
        $shouldReindex = false;

        $alive = retry(static::RETRY_TIMES, function ($attempts) use (&$shouldReindex, $elasticsearch) {
            try {
                $elasticsearch->ping();

                $this->info(ES_AL_01);

                report_logger(['message' => ES_AL_01]);

                return true;
            } catch (Throwable $e) {
                report_logger(['ErrorCode' => 'ES_NAL_01'], ['ErrorDetails' => report_logger()->formatError(ES_NAL_01, $e)]);

                $this->line(sprintf('<comment>%s</comment> Attempt: %s', ES_NAL_01, $attempts));

                static::restartElasticsearch();

                $shouldReindex = true;

                throw $e;
            }
        }, static::SLEEP_ON_RETRY);

        if ($alive && $shouldReindex) {
            $this->call('eq:search-reindex');
        }
    }

    protected static function restartElasticsearch(): void
    {
        $command = Php72::php_os_family() === 'Linux'
            ? 'service elasticsearch start'
            : 'elasticsearch';

        exec($command, $output);
    }
}

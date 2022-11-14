<?php

namespace App\Jobs\Search;

use App\Enum\Priority;
use App\Models\User;
use App\Services\Elasticsearch\IndexService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RebuildSearch implements ShouldQueue, ShouldBeUniqueUntilProcessing
{
    use Dispatchable, InteractsWithQueue, SerializesModels, Queueable;

    public function __construct(
        public readonly iterable $models,
        public readonly ?Model $causer,
    ) {
    }

    public function handle(
        IndexService $service,
        LoggerInterface $logger = new NullLogger()
    ): void {
        $service->bulkBuildModelIndices($this->models);

        $logger->info('Search rebuild: completed.', [
            'causer_id' => $this->causer?->getKey(),
        ]);

        if ($this->causer instanceof User) {
            notification()
                ->for($this->causer)
                ->priority(Priority::High)
                ->message("Search rebuild completed.")
                ->push();
        }
    }

    public function fail(\Throwable $exception = null)
    {
        if ($this->causer instanceof User) {
            notification()
                ->for($this->causer)
                ->priority(Priority::High)
                ->message("Search rebuild failed.")
                ->push();
        }
    }
}
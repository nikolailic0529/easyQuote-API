<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Elasticsearch\Client as Elasticsearch;

class IndexModel implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    /** @var \Illuminate\Database\Eloquent\Model */
    protected $model;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(Elasticsearch $elasticsearch)
    {
        rescue(function () use ($elasticsearch) {
            $elasticsearch->index([
                'index' => $this->model->getSearchIndex(),
                'id'    => $this->model->getKey(),
                'body'  => $this->model->toSearchArray(),
            ]);
        });
    }
}
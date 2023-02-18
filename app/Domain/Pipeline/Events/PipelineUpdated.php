<?php

namespace App\Domain\Pipeline\Events;

use App\Domain\Pipeline\Models\Pipeline;
use Illuminate\Queue\SerializesModels;

final class PipelineUpdated
{
    use SerializesModels;

    private \App\Domain\Pipeline\Models\Pipeline $pipeline;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }
}

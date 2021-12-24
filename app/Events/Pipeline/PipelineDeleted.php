<?php

namespace App\Events\Pipeline;

use App\Models\Pipeline\Pipeline;
use Illuminate\Queue\SerializesModels;

final class PipelineDeleted
{
    use SerializesModels;

    private Pipeline $pipeline;

    /**
     * Create a new event instance.
     *
     * @param \App\Models\Pipeline\Pipeline $pipeline
     * @return void
     */
    public function __construct(Pipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    /**
     * @return \App\Models\Pipeline\Pipeline
     */
    public function getPipeline(): Pipeline
    {
        return $this->pipeline;
    }
}

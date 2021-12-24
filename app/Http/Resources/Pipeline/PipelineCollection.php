<?php

namespace App\Http\Resources\Pipeline;

use Illuminate\Database\Eloquent\Collection;

class PipelineCollection extends Collection
{
    public array $availableIncludes = [
      'pipelineStages',
    ];

    public array $translatedIncludes = [
      'pipelineStages' => 'pipelineStages:id,pipeline_id,stage_name,stage_order'
    ];
}

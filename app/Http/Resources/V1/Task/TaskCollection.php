<?php

namespace App\Http\Resources\V1\Task;

use App\Http\Resources\V1\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return TaskListResource::class;
    }
}

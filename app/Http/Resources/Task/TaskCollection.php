<?php

namespace App\Http\Resources\Task;

use App\Http\Resources\Concerns\TransformsCollection;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TaskCollection extends ResourceCollection
{
    use TransformsCollection;

    protected function resource(): string
    {
        return TaskListResource::class;
    }
}

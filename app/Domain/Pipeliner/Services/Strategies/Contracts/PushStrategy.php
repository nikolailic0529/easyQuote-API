<?php

namespace App\Domain\Pipeliner\Services\Strategies\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PushStrategy extends SyncStrategy
{
    public function sync(Model $model): void;
}

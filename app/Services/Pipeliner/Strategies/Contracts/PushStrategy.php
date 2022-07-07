<?php

namespace App\Services\Pipeliner\Strategies\Contracts;

use Illuminate\Database\Eloquent\Model;

interface PushStrategy extends SyncStrategy
{
    public function sync(Model $model): void;
}
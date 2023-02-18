<?php

namespace App\Domain\Task\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface LinkedToTasks
{
    public function tasks(): MorphToMany;
}

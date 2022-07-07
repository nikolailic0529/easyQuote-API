<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface LinkedToTasks
{
    public function tasks(): MorphToMany;
}
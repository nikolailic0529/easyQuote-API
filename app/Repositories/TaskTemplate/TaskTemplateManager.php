<?php

namespace App\Repositories\TaskTemplate;

use Illuminate\Container\RewindableGenerator;

class TaskTemplateManager
{
    protected RewindableGenerator $generator;

    public function __construct(RewindableGenerator $generator)
    {
        $this->generator = $generator;
    }

    public function getIterator()
    {
        return $this->generator->getIterator();
    }
}
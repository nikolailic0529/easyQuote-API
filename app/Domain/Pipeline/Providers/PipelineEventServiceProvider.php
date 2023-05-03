<?php

namespace App\Domain\Pipeline\Providers;

use App\Domain\Pipeline\Listeners\PipelineEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class PipelineEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        PipelineEventAuditor::class,
    ];
}

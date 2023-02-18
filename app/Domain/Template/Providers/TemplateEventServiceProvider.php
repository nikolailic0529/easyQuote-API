<?php

namespace App\Domain\Template\Providers;

use App\Domain\Template\Listeners\OpportunityFormEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class TemplateEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        OpportunityFormEventAuditor::class,
    ];
}

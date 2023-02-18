<?php

namespace App\Domain\QuoteFile\Providers;

use App\Domain\QuoteFile\Listeners\ImportableColumnEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class ImportableColumnEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        ImportableColumnEventAuditor::class,
    ];
}

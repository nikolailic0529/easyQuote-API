<?php

namespace App\Domain\QuoteFile\Providers;

use App\Domain\QuoteFile\Listeners\DocumentMappingSyncSubscriber;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class DocumentMappingEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        DocumentMappingSyncSubscriber::class,
    ];
}

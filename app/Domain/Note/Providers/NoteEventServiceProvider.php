<?php

namespace App\Domain\Note\Providers;

use App\Domain\Note\Listeners\NoteEventAuditor;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;

class NoteEventServiceProvider extends EventServiceProvider
{
    protected $subscribe = [
        NoteEventAuditor::class,
    ];
}

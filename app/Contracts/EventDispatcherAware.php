<?php

namespace App\Contracts;

use Illuminate\Contracts\Events\Dispatcher;

interface EventDispatcherAware
{
    public function setEventDispatcher(Dispatcher $dispatcher): static;
}
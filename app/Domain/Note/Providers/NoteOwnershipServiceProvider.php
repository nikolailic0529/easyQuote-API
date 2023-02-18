<?php

namespace App\Domain\Note\Providers;

use App\Domain\Note\Services\NoteOwnershipService;
use App\Domain\Shared\Ownership\Contracts\ChangeOwnershipStrategy;
use Illuminate\Support\ServiceProvider;

class NoteOwnershipServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->tag(NoteOwnershipService::class, ChangeOwnershipStrategy::class);
    }
}

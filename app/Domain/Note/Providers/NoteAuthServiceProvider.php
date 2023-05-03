<?php

namespace App\Domain\Note\Providers;

use App\Domain\Note\Models\Note;
use App\Domain\Note\Policies\NotePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class NoteAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Note::class, NotePolicy::class);
    }
}

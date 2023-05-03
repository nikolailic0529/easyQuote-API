<?php

namespace App\Domain\Pipeline\Providers;

use App\Domain\Pipeline\Models\Pipeline;
use App\Domain\Pipeline\Policies\PipelinePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PipelineAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Pipeline::class, PipelinePolicy::class);
    }
}

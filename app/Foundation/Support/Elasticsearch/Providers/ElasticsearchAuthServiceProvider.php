<?php

namespace App\Foundation\Support\Elasticsearch\Providers;

use App\Foundation\Support\Elasticsearch\Policies\SearchPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ElasticsearchAuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('rebuildSearch', [SearchPolicy::class, 'rebuildSearch']);
    }
}

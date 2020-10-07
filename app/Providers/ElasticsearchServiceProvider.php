<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Support\DeferrableProvider;
use Elasticsearch\{
    Client as ElasticsearchClient,
    ClientBuilder as ElasticsearchBuilder
};

class ElasticsearchServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ElasticsearchClient::class, fn () => ElasticsearchBuilder::create()->setHosts(
            $this->app['config']->get('services.search.hosts')
        )->build());

        $this->app->alias(ElasticsearchClient::class, 'elasticsearch.client');
    }

    public function provides()
    {
        return [
            ElasticsearchClient::class,
            'elasticsearch.client',
        ];
    }
}

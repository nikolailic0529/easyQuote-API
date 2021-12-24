<?php

namespace App\Providers;

use Elasticsearch\{Client as ElasticsearchClient,
    ClientBuilder as ElasticsearchBuilder,
    ConnectionPool\Selectors\StickyRoundRobinSelector};
use Illuminate\Contracts\Support\DeferrableProvider;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider implements DeferrableProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ElasticsearchClient::class, function () {
            return ElasticsearchBuilder::create()
                ->setHosts($this->app['config']['services.elasticsearch.hosts'])
                ->setSelector(StickyRoundRobinSelector::class)
                ->build();
        });

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

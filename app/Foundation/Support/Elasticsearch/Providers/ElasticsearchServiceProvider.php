<?php

namespace App\Foundation\Support\Elasticsearch\Providers;

use App\Foundation\Support\Elasticsearch\Jobs\RebuildSearch;
use App\Foundation\Support\Elasticsearch\Services\RebuildSearchQueueService;
use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\ClientBuilder as ElasticsearchBuilder;
use Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;

class ElasticsearchServiceProvider extends ServiceProvider
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

        $this->app->afterResolving(RebuildSearchQueueService::class,
            static function (RebuildSearchQueueService $concrete, Container $container): void {
                $concrete->setLogger(
                    $container['log']->channel('search')
                );
            });

        $this->app->bindMethod([RebuildSearch::class, 'handle'],
            static function (RebuildSearch $concrete, Container $container): mixed {
                return $container->call($concrete->handle(...), ['logger' => $container['log']->channel('search')]);
            });
    }

    public function provides()
    {
        return [
            ElasticsearchClient::class,
            'elasticsearch.client',
        ];
    }
}

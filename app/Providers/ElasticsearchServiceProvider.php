<?php

namespace App\Providers;

use App\Jobs\Search\RebuildSearch;
use App\Services\Elasticsearch\RebuildSearchQueueService;
use Elasticsearch\{Client as ElasticsearchClient,
    ClientBuilder as ElasticsearchBuilder,
    ConnectionPool\Selectors\StickyRoundRobinSelector
};
use Illuminate\Contracts\Container\Container;
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

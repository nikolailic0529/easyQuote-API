<?php

namespace App\Domain\QuoteFile\Providers;

use App\Domain\QuoteFile\Contracts\QuoteFileRepositoryInterface;
use App\Domain\QuoteFile\Repositories\QuoteFileRepository;
use App\Domain\QuoteFile\Services\QuoteFileFilesystem;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\FilesystemInterface;

class QuoteFileServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->when(QuoteFileFilesystem::class)->needs(FilesystemInterface::class)
            ->give(static function (Container $container): FilesystemInterface {
                return $container['filesystem']->disk()->getDriver();
            });

        $this->app->singleton(QuoteFileRepositoryInterface::class, QuoteFileRepository::class);
        $this->app->alias(QuoteFileRepositoryInterface::class, 'quotefile.repository');
    }
}

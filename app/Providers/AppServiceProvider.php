<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->when(\Spatie\PdfToText\Pdf::class)->needs('$binPath')->give(fn () => $this->app['config']->get('pdfparser.pdftotext.bin_path'));

        $this->app->bind(
            \Symfony\Component\Validator\Validator\ValidatorInterface::class,
            function () {
                return \Symfony\Component\Validator\Validation::createValidatorBuilder()
                    ->enableAnnotationMapping(true)
                    ->addDefaultDoctrineAnnotationReader()
                    ->getValidator();
            }
        );
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);
    }
}

<?php

namespace App\Domain\DocumentProcessing\Providers;

use Barryvdh\Snappy\PdfWrapper;
use Illuminate\Support\ServiceProvider;

class SnappyServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->alias('snappy.pdf.wrapper', PdfWrapper::class);
    }
}

<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = $this->makeApp();

        if ($app->environment() !== 'testing') {
            $this->clearCache();
            $app = $this->makeApp();
        }

        return $app;
    }

    /**
     * Create a new Application instance.
     *
     * @return void
     */
    protected function makeApp()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function clearCache(): void
    {
        Artisan::call('optimize:clear');
    }
}

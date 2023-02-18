<?php

namespace Bootstrap;

use Illuminate\Foundation\Application as Base;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;

class Application extends Base
{
    /**
     * Create a new Illuminate application instance.
     *
     * @param string|null $basePath
     *
     * @return void
     */
    public function __construct($basePath = null)
    {
        parent::__construct($basePath);

        $this->afterBootstrapping(LoadConfiguration::class, function () {
            $this->useCustomStoragePath();
        });
    }

    protected function useCustomStoragePath()
    {
        $storagePath = $this['config']->get('paths.storage');

        if (!is_null($storagePath)) {
            $this->useStoragePath($this['config']->get('paths.storage'));
        }
    }
}

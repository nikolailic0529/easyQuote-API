<?php

namespace Bootstrap;

use App\Facades\Maintenance;
use Illuminate\Foundation\Application as Base;

class Application extends Base
{
    /**
     * Determine if the application is currently down for maintenance.
     *
     * @return bool
     */
    public function isDownForMaintenance()
    {
        return file_exists($this->storagePath().'/framework/down') || Maintenance::running();
    }
}
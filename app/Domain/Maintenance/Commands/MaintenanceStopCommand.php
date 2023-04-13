<?php

namespace App\Domain\Maintenance\Commands;

use App\Domain\Maintenance\Jobs\UpFromMaintenanceMode;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;

class MaintenanceStopCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'eq:maintenance-stop';

    /**
     * @var string
     */
    protected $description = 'Bring the application out of maintenance mode';

    public function handle(Dispatcher $dispatcher): int
    {
        $dispatcher->dispatchSync(new UpFromMaintenanceMode());

        return self::SUCCESS;
    }
}

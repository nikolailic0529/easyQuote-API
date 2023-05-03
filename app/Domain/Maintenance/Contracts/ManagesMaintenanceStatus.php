<?php

namespace App\Domain\Maintenance\Contracts;

use App\Domain\Build\Models\Build;
use App\Domain\Maintenance\Enum\MaintenanceStatusEnum;

interface ManagesMaintenanceStatus
{
    /**
     * Get maintenance status.
     */
    public function status(): MaintenanceStatusEnum;

    /**
     * Interpret maintenance status of given Build instance.
     *
     * @return mixed
     */
    public function interpretStatusOf(?Build $build): MaintenanceStatusEnum;

    /**
     * Determine whether maintenance is running.
     */
    public function running(): bool;

    /**
     * Determine whether maintenance is stopped.
     */
    public function stopped(): bool;

    /**
     * Determine whether maintenance is scheduled.
     */
    public function scheduled(): bool;

    /**
     * Put maintenance data.
     */
    public function writeMaintenanceData(): void;

    /**
     * Retrieve stored maintenance data.
     */
    public function getMaintenanceData(): array;
}

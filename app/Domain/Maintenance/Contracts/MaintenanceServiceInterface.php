<?php

namespace App\Domain\Maintenance\Contracts;

use App\Domain\Build\Models\Build;

interface MaintenanceServiceInterface
{
    /**
     * Get maintenance status.
     */
    public function status(): int;

    /**
     * Interpret maintenance status of given Build instance.
     *
     * @return mixed
     */
    public function interpretStatusOf(?Build $build): int;

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
    public function putData(): void;

    /**
     * Retrieve stored maintenance data.
     */
    public function getData(): array;
}

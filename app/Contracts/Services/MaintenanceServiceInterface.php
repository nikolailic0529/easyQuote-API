<?php

namespace App\Contracts\Services;

use App\Models\System\Build;

interface MaintenanceServiceInterface
{
    /**
     * Get maintenance status.
     *
     * @return integer
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
     *
     * @return boolean
     */
    public function running(): bool;

    /**
     * Determine whether maintenance is stopped.
     *
     * @return boolean
     */
    public function stopped(): bool;

    /**
     * Determine whether maintenance is scheduled.
     *
     * @return boolean
     */
    public function scheduled(): bool;

    /**
     * Put maintenance data.
     *
     * @return void
     */
    public function putData(): void;

    /**
     * Retrieve stored maintenance data.
     *
     * @return array
     */
    public function getData(): array;
}
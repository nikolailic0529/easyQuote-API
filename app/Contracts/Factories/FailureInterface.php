<?php

namespace App\Contracts\Factories;

use App\Factories\Failure\FailureHelp;
use Illuminate\Support\Collection;

interface FailureInterface
{
    /**
     * Retrieve array with reasons and resolving the given exception issue.
     *
     * @param \Throwable $Throwable
     * @return \Illuminate\Support\Collection
     */
    public function helpFor(\Throwable $exception): FailureHelp;

    /**
     * Retrieve possible reasons of the given exception issue.
     *
     * @param \Throwable $Throwable
     * @return \Illuminate\Support\Collection
     */
    public function reasonsFor(\Throwable $exception): Collection;

    /**
     * Retrieve possible resolving of the given exception issue.
     *
     * @param \Throwable $exception
     * @return \Illuminate\Support\Collection
     */
    public function resolvingFor(\Throwable $exception): Collection;

    /**
     * Getter for retrieving all existing reasons.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getReasons(): Collection;

    /**
     * Getter for retrieving all existing resolving.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getResolving(): Collection;
}

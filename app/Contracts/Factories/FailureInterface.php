<?php

namespace App\Contracts\Factories;

use App\Factories\Failure\FailureHelp;
use Illuminate\Support\Collection;

interface FailureInterface
{
    /**
     * Retrieve array with reasons and resolving the given exception issue.
     *
     * @param \Exception $exception
     * @return \Illuminate\Support\Collection
     */
    public function helpFor(\Exception $exception): FailureHelp;

    /**
     * Retrieve possible reasons of the given exception issue.
     *
     * @param \Exception $exception
     * @return \Illuminate\Support\Collection
     */
    public function reasonsFor(\Exception $exception): Collection;

    /**
     * Retrieve possible resolving of the given exception issue.
     *
     * @param \Exception $exception
     * @return \Illuminate\Support\Collection
     */
    public function resolvingFor(\Exception $exception): Collection;

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

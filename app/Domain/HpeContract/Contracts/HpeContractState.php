<?php

namespace App\Domain\HpeContract\Contracts;

use App\Domain\HpeContract\DataTransferObjects\ImportResponse;
use App\Domain\HpeContract\DataTransferObjects\PreviewHpeContractData;
use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\HpeContract\Models\HpeContractFile;
use Illuminate\Support\Collection as BaseCollection;

interface HpeContractState
{
    /**
     * Process HPE Contract state.
     * If the HPE Contract is present as argument, we will assume process state for the given instance.
     * Otherwise, new HPE contract will be created.
     */
    public function processState(array $state, ?HpeContract $hpeContract = null): HpeContract;

    /**
     * Initiate a new HPE Contract Instance with new sequence number.
     */
    public function initiateHpeContractInstance(): HpeContract;

    /**
     * Make a new copy of the specified HPE Contract.
     */
    public function copy(HpeContract $hpeContract): array;

    /**
     * Submit the specified HPE Contract.
     */
    public function submit(HpeContract $hpeContract): bool;

    /**
     * Unravel the specified HPE Contract.
     */
    public function unravel(HpeContract $hpeContract): bool;

    /**
     * Mark as activated the specified HPE Contract.
     */
    public function activate(HpeContract $hpeContract): bool;

    /**
     * Mark as deactivated the specified HPE Contract.
     */
    public function deactivate(HpeContract $hpeContract): bool;

    /**
     * Delete the specified HPE Contract.
     */
    public function delete(HpeContract $hpeContract): bool;

    /**
     * Retrieve Imported HPE Contract Data.
     */
    public function processHpeContractData(HpeContract $hpeContract, HpeContractFile $hpeContractFile, ?ImportResponse $importResponse = null): bool;

    /**
     * Retrieve aggregated HPE Contract Data.
     */
    public function retrieveContractData(HpeContract $hpeContract): BaseCollection;

    /**
     * Retrieve HPE Contract Assets grouped by specific clauses.
     */
    public function retrieveSummarizedContractData(HpeContract $hpeContract): PreviewHpeContractData;

    /**
     * Mark as selected/unselected HPE Contract Assets.
     */
    public function markAssetsAsSelected(HpeContract $hpeContract, array $ids, bool $reject = false): bool;
}

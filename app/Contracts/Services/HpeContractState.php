<?php

namespace App\Contracts\Services;

use App\DTO\ImportResponse;
use App\DTO\PreviewHpeContractData;
use App\Models\HpeContract;
use App\Models\HpeContractData;
use App\Models\HpeContractFile;

interface HpeContractState
{
    /**
     * Process HPE Contract state.
     * If the HPE Contract is present as argument, we will asume process state for the given instance.
     * Otherwise new HPE contract will be created.
     *
     * @param  array $state
     * @param  HpeContract|null $hpeContract
     * @return HpeContract
     */
    public function processState(array $state, ?HpeContract $hpeContract = null);

    /**
     * Initiate a new HPE Contract Instance with new sequence number.
     *
     * @return HpeContract
     */
    public function initiateHpeContractInstance(): HpeContract;

    /**
     * Make a new copy of the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return mixed
     */
    public function copy(HpeContract $hpeContract);

    /**
     * Submit the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function submit(HpeContract $hpeContract): bool;

    /**
     * Unravel the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function unsubmit(HpeContract $hpeContract): bool;

    /**
     * Mark as activated the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function activate(HpeContract $hpeContract): bool;

    /**
     * Mark as deactivated the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function deactivate(HpeContract $hpeContract): bool;

    /**
     * Delete the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return boolean
     */
    public function delete(HpeContract $hpeContract): bool;

    /**
     * Associate the HPE Contract with specified HPE Contract File.
     *
     * @param HpeContract $hpeContract
     * @param HpeContractFile $hpeContractFile
     * @return void
     */
    public function associateHpeContractFile(HpeContract $hpeContract, HpeContractFile $hpeContractFile): void;

    /**
     * Retrieve Imported HPE Contract Data.
     *
     * @param HpeContract $hpeContract
     * @param HpeContractFile $hpeContractFile
     * @param ImportResponse|null $importResponse
     * @return boolean
     */
    public function processHpeContractData(HpeContract $hpeContract, HpeContractFile $hpeContractFile, ?ImportResponse $importResponse = null): bool;

    /**
     * Retrieve aggregated HPE Contract Data.
     *
     * @param HpeContract $hpeContract
     * @return \Illuminate\Support\Collection
     */
    public function retrieveContractData(HpeContract $hpeContract);

    /**
     * Retrieve HPE Contract Assets grouped by specific clauses.
     *
     * @param HpeContract $hpeContract
     * @return PreviewHpeContractData
     */
    public function retrieveSummarizedContractData(HpeContract $hpeContract): PreviewHpeContractData;

    /**
     * Mark as selected/unselected HPE Contract Assets.
     *
     * @param HpeContract $hpeContract
     * @param array $ids
     * @param boolean $reject
     * @return boolean
     */
    public function selectAssets(HpeContract $hpeContract, array $ids, bool $reject = false);
}

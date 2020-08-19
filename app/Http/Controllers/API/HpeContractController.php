<?php

namespace App\Http\Controllers\API;

use App\Contracts\Services\{HpeContractState, HpeExporter};
use App\DTO\ImportResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HpeContract\{Export, ImportStep, SelectAssets, StoreState, Submit};
use App\Http\Resources\HpeContract\HpeContract as Resource;
use App\Models\{HpeContract, HpeContractFile};
use App\Services\HpeContractFileService;

class HpeContractController extends Controller
{
    protected HpeContractState $processor;

    public function __construct(HpeContractState $processor)
    {
        $this->processor = $processor;

        $this->authorizeResource(HpeContract::class);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Display the data required for the first import step.
     *
     * @param  ImportStep $request
     * @return \Illuminate\Http\Response
     */
    public function showImportStepData(ImportStep $request)
    {
        return response()->json(
            $request->getData()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreState $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreState $request)
    {
        $resource = $this->processor->processState($request->validated());

        return response()->json(
            filter(Resource::make($resource))
        );
    }

    /**
     * Associate the specified HPE Contract File with HPE Contract and process import.
     *
     * @param  HpeContract $hpeContract
     * @param  HpeContractFile $hpeContractFile
     * @return \Illuminate\Http\Response
     */
    public function importHpeContract(HpeContract $hpeContract, HpeContractFile $hpeContractFile, HpeContractFileService $fileService)
    {
        $this->authorize('update', $hpeContract);

        return tap(
            $fileService->processImport($hpeContractFile),
            fn (ImportResponse $importResponse) =>
            $this->processor->processHpeContractData($hpeContract, $hpeContractFile, $importResponse)
        );
    }

    /**
     * Retrieve and aggregate HPE Contract Data.
     *
     * @param HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function reviewHpeContractData(HpeContract $hpeContract)
    {
        $this->authorize('view', $hpeContract);

        return response()->json(
            $this->processor->retrieveContractData($hpeContract)
        );
    }

    /**
     * Display summaried HPE Contract Data.
     *
     * @param  HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function previewHpeContract(HpeContract $hpeContract)
    {
        $this->authorize('view', $hpeContract);

        return response()->json(
            $this->processor->retrieveSummarizedContractData($hpeContract)
        );
    }

    /**
     * Validate and submit the specified HPE Contract.
     *
     * @param  Submit $request
     * @param  HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function submitHpeContract(Submit $request, HpeContract $hpeContract)
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->submit($hpeContract)
        );
    }

    /**
     * Unravel the specified HPE Contract.
     *
     * @param  HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function unsubmitHpeContract(HpeContract $hpeContract)
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->unsubmit($hpeContract)
        );
    }

    /**
     * Validate and activate the specified HPE Contract.
     *
     * @param  HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function activateHpeContract(HpeContract $hpeContract)
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->activate($hpeContract)
        );
    }

    /**
     * Deactivate the specified HPE Contract.
     *
     * @param  HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function deactivateHpeContract(HpeContract $hpeContract)
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->deactivate($hpeContract)
        );
    }

    /**
     * Make a new copy of the specified HPE Contract in repository.
     *
     * @param  HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function copyHpeContract(HpeContract $hpeContract)
    {
        $this->authorize('copy', $hpeContract);

        return response()->json(
            $this->processor->copy($hpeContract)
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\HpeContract  $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function show(HpeContract $hpeContract)
    {
        return response()->json(
            filter(Resource::make($hpeContract))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  StoreState  $request
     * @param  \App\Models\HpeContract  $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function update(StoreState $request, HpeContract $hpeContract)
    {
        $resource = $this->processor->processState($request->validated(), $hpeContract);

        return response()->json(
            filter(Resource::make($resource))
        );
    }

    /**
     * Mark as selected specified assets in HPE Contract.
     *
     * @param  SelectAssets $request
     * @param  HpeContract $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function selectAssets(SelectAssets $request, HpeContract $hpeContract)
    {
        return response()->json(
            $this->processor->selectAssets(
                $hpeContract,
                $request->getIds(),
                $request->boolean('reject')
            )
        );
    }

    /**
     * Export to PDF the specified HPE Contract.
     *
     * @param  Export $request
     * @param  HpeContract $hpeContract
     * @param  HpeExporter $exporter
     * @return \Illuminate\Http\Response
     */
    public function exportHpeContract(Export $request, HpeContract $hpeContract, HpeExporter $exporter)
    {
        return response()->download(
            $exporter->export(
                $hpeContract->hpeContractTemplate,
                $this->processor->retrieveSummarizedContractData($hpeContract)
            )
        );
    }

    /**
     * Web Preview the specified HPE Contract.
     *
     * @param  Export $request
     * @param  HpeContract $hpeContract
     * @param  HpeExporter $exporter
     * @return \Illuminate\Http\Response
     */
    public function viewHpeContract(Export $request, HpeContract $hpeContract, HpeExporter $exporter)
    {
        return $exporter->export(
            $hpeContract->hpeContractTemplate,
            $this->processor->retrieveSummarizedContractData($hpeContract),
            true
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\HpeContract  $hpeContract
     * @return \Illuminate\Http\Response
     */
    public function destroy(HpeContract $hpeContract)
    {
        return response()->json(
            $this->processor->delete($hpeContract)
        );
    }
}

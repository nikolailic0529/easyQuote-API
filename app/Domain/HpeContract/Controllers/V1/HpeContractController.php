<?php

namespace App\Domain\HpeContract\Controllers\V1;

use App\Domain\HpeContract\Contracts\HpeContractState;
use App\Domain\HpeContract\Contracts\{HpeExporter};
use App\Domain\HpeContract\DataTransferObjects\ImportResponse;
use App\Domain\HpeContract\Models\HpeContract;
use App\Domain\HpeContract\Models\{HpeContractFile};
use App\Domain\HpeContract\Requests\ExportRequest;
use App\Domain\HpeContract\Requests\ImportHpeContractRequest;
use App\Domain\HpeContract\Requests\ImportStepRequest;
use App\Domain\HpeContract\Requests\SelectAssetsRequest;
use App\Domain\HpeContract\Requests\StoreStateRequest;
use App\Domain\HpeContract\Requests\SubmitRequest;
use App\Domain\HpeContract\Resources\V1\HpeContract as Resource;
use App\Domain\HpeContract\Services\HpeContractFileService;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class HpeContractController extends Controller
{
    protected HpeContractState $processor;

    public function __construct(HpeContractState $processor)
    {
        $this->processor = $processor;

        $this->authorizeResource(HpeContract::class);
    }

    /**
     * Display the data required for the first import step.
     */
    public function showImportStepData(ImportStepRequest $request): JsonResponse
    {
        return response()->json(
            $request->getData()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStateRequest $request): JsonResponse
    {
        $resource = $this->processor->processState($request->validated());

        return response()->json(
            filter(Resource::make($resource))
        );
    }

    /**
     * Associate the specified HPE Contract File with HPE Contract and process import.
     *
     * @throws AuthorizationException
     */
    public function importHpeContract(ImportHpeContractRequest $request,
                                      HpeContract $hpeContract,
                                      HpeContractFile $hpeContractFile,
                                      HpeContractFileService $fileService): ImportResponse
    {
        $this->authorize('update', $hpeContract);

        return tap($fileService->processImport($hpeContractFile, $request->getImportData()), function (ImportResponse $response) use ($hpeContractFile, $hpeContract) {
            $this->processor->processHpeContractData($hpeContract, $hpeContractFile, $response);
        });
    }

    /**
     * Retrieve and aggregate HPE Contract Data.
     *
     * @throws AuthorizationException
     */
    public function reviewHpeContractData(HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('view', $hpeContract);

        return response()->json(
            $this->processor->retrieveContractData($hpeContract)
        );
    }

    /**
     * Display summarized HPE Contract Data.
     *
     * @throws AuthorizationException
     */
    public function previewHpeContract(HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('view', $hpeContract);

        return response()->json(
            $this->processor->retrieveSummarizedContractData($hpeContract)
        );
    }

    /**
     * Validate and submit the specified HPE Contract.
     */
    public function submitHpeContract(SubmitRequest $request, HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->submit($hpeContract)
        );
    }

    /**
     * Unravel the specified HPE Contract.
     *
     * @throws AuthorizationException
     */
    public function unsubmitHpeContract(HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->unravel($hpeContract)
        );
    }

    /**
     * Validate and activate the specified HPE Contract.
     *
     * @throws AuthorizationException
     */
    public function activateHpeContract(HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->activate($hpeContract)
        );
    }

    /**
     * Deactivate the specified HPE Contract.
     *
     * @throws AuthorizationException
     */
    public function deactivateHpeContract(HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->deactivate($hpeContract)
        );
    }

    /**
     * Make a new copy of the specified HPE Contract in repository.
     *
     * @throws AuthorizationException
     */
    public function copyHpeContract(HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('copy', $hpeContract);

        return response()->json(
            $this->processor->copy($hpeContract)
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(HpeContract $hpeContract): JsonResponse
    {
        return response()->json(
            filter(Resource::make($hpeContract))
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreStateRequest $request, HpeContract $hpeContract): JsonResponse
    {
        $resource = $this->processor->processState($request->validated(), $hpeContract);

        return response()->json(
            filter(Resource::make($resource))
        );
    }

    /**
     * Mark as selected specified assets in HPE Contract.
     */
    public function selectAssets(SelectAssetsRequest $request, HpeContract $hpeContract): JsonResponse
    {
        return response()->json(
            $this->processor->markAssetsAsSelected(
                $hpeContract,
                $request->getIds(),
                $request->boolean('reject')
            )
        );
    }

    /**
     * Export to PDF the specified HPE Contract.
     *
     * @param \App\Domain\HpeContract\Contracts\HpeExporter $exporter
     */
    public function exportHpeContract(ExportRequest $request, HpeContract $hpeContract, HpeExporter $exporter): Responsable
    {
        return $exporter->export(
            $hpeContract->hpeContractTemplate,
            $this->processor->retrieveSummarizedContractData($hpeContract)
        );
    }

    /**
     * Web Preview the specified HPE Contract.
     *
     * @param \App\Domain\HpeContract\Contracts\HpeExporter $exporter
     */
    public function viewHpeContract(ExportRequest $request, HpeContract $hpeContract, HpeExporter $exporter): Responsable
    {
        return $exporter->export(
            $hpeContract->hpeContractTemplate,
            $this->processor->retrieveSummarizedContractData($hpeContract)
        )->stream();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HpeContract $hpeContract): JsonResponse
    {
        return response()->json(
            $this->processor->delete($hpeContract)
        );
    }
}

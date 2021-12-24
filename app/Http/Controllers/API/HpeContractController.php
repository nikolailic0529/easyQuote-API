<?php

namespace App\Http\Controllers\API;

use App\Contracts\Services\{HpeContractState, HpeExporter};
use App\DTO\ImportResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\HpeContract\{Export, ImportHpeContract, ImportStep, SelectAssets, StoreState, Submit};
use App\Http\Resources\HpeContract\HpeContract as Resource;
use App\Models\{HpeContract, HpeContractFile};
use App\Services\HpeContractFileService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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
     *
     * @param  ImportStep $request
     * @return JsonResponse
     */
    public function showImportStepData(ImportStep $request): JsonResponse
    {
        return response()->json(
            $request->getData()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreState $request
     * @return JsonResponse
     */
    public function store(StoreState $request): JsonResponse
    {
        $resource = $this->processor->processState($request->validated());

        return response()->json(
            filter(Resource::make($resource))
        );
    }

    /**
     * Associate the specified HPE Contract File with HPE Contract and process import.
     *
     * @param ImportHpeContract $request
     * @param HpeContract $hpeContract
     * @param HpeContractFile $hpeContractFile
     * @param HpeContractFileService $fileService
     * @return ImportResponse
     * @throws AuthorizationException
     */
    public function importHpeContract(ImportHpeContract $request,
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
     * @param HpeContract $hpeContract
     * @return JsonResponse
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
     * @param HpeContract $hpeContract
     * @return JsonResponse
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
     *
     * @param  Submit $request
     * @param  HpeContract $hpeContract
     * @return JsonResponse
     */
    public function submitHpeContract(Submit $request, HpeContract $hpeContract): JsonResponse
    {
        $this->authorize('update', $hpeContract);

        return response()->json(
            $this->processor->submit($hpeContract)
        );
    }

    /**
     * Unravel the specified HPE Contract.
     *
     * @param HpeContract $hpeContract
     * @return JsonResponse
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
     * @param HpeContract $hpeContract
     * @return JsonResponse
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
     * @param HpeContract $hpeContract
     * @return JsonResponse
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
     * @param HpeContract $hpeContract
     * @return JsonResponse
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
     *
     * @param HpeContract $hpeContract
     * @return JsonResponse
     */
    public function show(HpeContract $hpeContract): JsonResponse
    {
        return response()->json(
            filter(Resource::make($hpeContract))
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  StoreState  $request
     * @param HpeContract $hpeContract
     * @return JsonResponse
     */
    public function update(StoreState $request, HpeContract $hpeContract): JsonResponse
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
     * @return JsonResponse
     */
    public function selectAssets(SelectAssets $request, HpeContract $hpeContract): JsonResponse
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
     * @param  Export $request
     * @param  HpeContract $hpeContract
     * @param  HpeExporter $exporter
     * @return Responsable
     */
    public function exportHpeContract(Export $request, HpeContract $hpeContract, HpeExporter $exporter): Responsable
    {
        return $exporter->export(
            $hpeContract->hpeContractTemplate,
            $this->processor->retrieveSummarizedContractData($hpeContract)
        );
    }

    /**
     * Web Preview the specified HPE Contract.
     *
     * @param  Export $request
     * @param  HpeContract $hpeContract
     * @param  HpeExporter $exporter
     * @return Responsable
     */
    public function viewHpeContract(Export $request, HpeContract $hpeContract, HpeExporter $exporter): Responsable
    {
        return $exporter->export(
            $hpeContract->hpeContractTemplate,
            $this->processor->retrieveSummarizedContractData($hpeContract)
        )->stream();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param HpeContract $hpeContract
     * @return JsonResponse
     */
    public function destroy(HpeContract $hpeContract): JsonResponse
    {
        return response()->json(
            $this->processor->delete($hpeContract)
        );
    }
}

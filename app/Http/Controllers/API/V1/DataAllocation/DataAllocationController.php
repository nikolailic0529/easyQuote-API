<?php

namespace App\Http\Controllers\API\V1\DataAllocation;

use App\DTO\DataAllocation\DataAllocationData;
use App\DTO\DataAllocation\DataAllocationFileData;
use App\DTO\DataAllocation\DataAllocationInListData;
use App\DTO\DataAllocation\Stages\ImportStageData;
use App\DTO\DataAllocation\Stages\InitStageData;
use App\DTO\DataAllocation\Stages\ResultsStageData;
use App\DTO\DataAllocation\Stages\ReviewStageData;
use App\DTO\File\UploadFileData;
use App\Http\Controllers\Controller;
use App\Models\DataAllocation\DataAllocation;
use App\Queries\DataAllocationQueries;
use App\Services\DataAllocation\DataAllocationEntityService;
use App\Services\DataAllocation\DataAllocationFileEntityService;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\LaravelData\PaginatedDataCollection;

class DataAllocationController extends Controller
{
    public function __construct(protected Guard $guard)
    {
    }

    public function paginateDataAllocations(Request $request, DataAllocationQueries $queries): PaginatedDataCollection
    {
        $this->authorize('viewAny', DataAllocation::class);

        return DataAllocationInListData::collection($queries->listDataAllocationsQuery($request)->apiPaginate());
    }

    public function initializeDataAllocation(
        InitStageData $data,
        DataAllocationEntityService $service
    ): DataAllocationData {
        $this->authorize('create', DataAllocation::class);

        return DataAllocationData::from($service->setCauser($this->guard->user())->processInitStage($data));
    }

    public function showDataAllocation(Request $request, DataAllocation $allocation): DataAllocationData
    {
        $this->authorize('view', $allocation);

        return DataAllocationData::from($allocation);
    }

    public function destroyDataAllocation(
        Request $request,
        DataAllocationEntityService $service,
        DataAllocation $allocation
    ): Response {
        $this->authorize('delete', $allocation);

        $service->setCauser($this->guard->user())->deleteDataAllocation($allocation);

        return response()->noContent();
    }

    public function storeDataAllocationFile(
        UploadFileData $data,
        DataAllocationFileEntityService $service,
        DataAllocation $allocation
    ): DataAllocationFileData {
        $this->authorize('update', $allocation);

        return DataAllocationFileData::from($service->createDataAllocationFile($data));
    }

    public function processImportStage(
        ImportStageData $data,
        DataAllocationEntityService $service,
        DataAllocation $allocation
    ): DataAllocationData {
        $this->authorize('processImportStage', $allocation);

        return DataAllocationData::from($service->setCauser($this->guard->user())
            ->processImportStage($allocation, $data));
    }

    public function processReviewStage(
        ReviewStageData $data,
        DataAllocationEntityService $service,
        DataAllocation $allocation
    ) {
        $this->authorize('processReviewStage', $allocation);

        return DataAllocationData::from(
            $service->setCauser($this->guard->user())
                ->processReviewStage($allocation, $data)
        );
    }

    public function processResultsStage(
        ResultsStageData $data,
        DataAllocationEntityService $service,
        DataAllocation $allocation
    ) {
        $this->authorize('processResultsStage', $allocation);

        return DataAllocationData::from(
            $service->setCauser($this->guard->user())
                ->processResultsStage($allocation, $data)
        );
    }
}
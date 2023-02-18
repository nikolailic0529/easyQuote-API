<?php

namespace App\Domain\HpeContract\Controllers\V1;

use App\Domain\HpeContract\Requests\StoreFileRequest;
use App\Domain\HpeContract\Services\HpeContractFileService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;

class HpeContractFileController extends Controller
{
    protected HpeContractFileService $service;

    public function __construct(HpeContractFileService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly uploaded hpe contract file.
     */
    public function __invoke(StoreFileRequest $request): JsonResponse
    {
        return response()->json(
            $this->service->store($request->file('file')),
            JsonResponse::HTTP_CREATED
        );
    }
}

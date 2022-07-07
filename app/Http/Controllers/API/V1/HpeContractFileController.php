<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\HpeContractFile\StoreFile;
use App\Services\HpeContractFileService;
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
     *
     * @param  StoreFile $request
     * @return JsonResponse
     */
    public function __invoke(StoreFile $request): JsonResponse
    {
        return response()->json(
            $this->service->store($request->file('file')),
            JsonResponse::HTTP_CREATED
        );
    }
}

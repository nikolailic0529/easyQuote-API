<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\HpeContractFile\StoreFile;
use App\Models\HpeContractFile;
use App\Services\HpeContractFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(StoreFile $request)
    {
        return response()->json(
            $this->service->store($request->file('file')),
            JsonResponse::HTTP_CREATED
        );
    }
}

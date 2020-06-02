<?php

namespace App\Http\Controllers\API;

use App\DTO\ServiceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lookup\Service;
use App\Http\Resources\Lookup\Service as ServiceResource;
use App\Services\ServiceLookup;
use Illuminate\Http\Response;

class ServiceController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Service  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Service $request, ServiceLookup $service)
    {
        $result = $service->getService(
            $request->vendor(),
            $request->serial_number,
            $request->product_number
        );

        return static::buildResponse($result);
    }

    protected static function buildResponse($result)
    {
        if ($result instanceof ServiceData) {
            return response()->json(
              ServiceResource::make($result)
            );
        }

        return response()->json(['message' => SUN_01], Response::HTTP_NOT_FOUND);
    }
}

<?php

namespace App\Http\Controllers\API;

use App\DTO\VendorServices\WarrantyLookupResult;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lookup\PerformWarrantyLookup;
use App\Http\Resources\Lookup\Service as ServiceResource;
use App\Services\Exceptions\ServiceLookupRouteException;
use App\Services\VendorServices\WarrantyLookupService;
use Illuminate\Http\JsonResponse;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    /**
     * Perform warranty lookup request.
     *
     * @param PerformWarrantyLookup $request
     * @param WarrantyLookupService $service
     * @return JsonResponse
     * @throws ServiceLookupRouteException
     * @throws InvalidArgumentException
     */
    public function __invoke(PerformWarrantyLookup $request,
                             WarrantyLookupService $service): JsonResponse
    {
        $result = $service->getWarranty(
            vendorCode: $request->getVendorCode(),
            serial: $request->getSerialNumber(),
            sku: $request->getProductNumber(),
            countryCode: $request->getCountryCode(),
        );

        return static::buildResponseFromResult($result);
    }

    protected static function buildResponseFromResult(?WarrantyLookupResult $result): JsonResponse
    {
        if (is_null($result)) {
            return response()->json(['message' => SUN_01], Response::HTTP_NOT_FOUND);
        }

        return response()->json(
            ServiceResource::make($result)
        );
    }
}

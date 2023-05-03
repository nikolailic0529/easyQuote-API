<?php

namespace App\Domain\VendorServices\Controllers\V1;

use App\Domain\VendorServices\DataTransferObjects\WarrantyLookupResult;
use App\Domain\VendorServices\Requests\PerformWarrantyLookupRequest;
use App\Domain\VendorServices\Resources\V1\Service as ServiceResource;
use App\Domain\VendorServices\Services\WarrantyLookupService;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;
use Psr\SimpleCache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class ServiceController extends Controller
{
    /**
     * Perform warranty lookup request.
     *
     * @throws \App\Domain\VendorServices\Exceptions\ServiceLookupRouteException
     * @throws InvalidArgumentException
     */
    public function __invoke(PerformWarrantyLookupRequest $request,
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

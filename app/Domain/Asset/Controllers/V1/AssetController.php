<?php

namespace App\Domain\Asset\Controllers\V1;

use App\Domain\Asset\Models\Asset;
use App\Domain\Asset\Queries\AssetCategoryQueries;
use App\Domain\Asset\Queries\AssetQueries;
use App\Domain\Asset\Requests\CreateAssetRequest;
use App\Domain\Asset\Requests\PaginateAssetsRequest;
use App\Domain\Asset\Requests\UniquenessRequest;
use App\Domain\Asset\Requests\UpdateAssetRequest;
use App\Domain\Asset\Resources\V1\AssetList;
use App\Domain\Asset\Resources\V1\AssetWithIncludes;
use App\Domain\Asset\Services\AssetEntityService;
use App\Domain\Company\Queries\CompanyQueries;
use App\Domain\Company\Resources\V1\CompanyOfAsset;
use App\Domain\Vendor\Contracts\VendorRepositoryInterface as Vendors;
use App\Foundation\Http\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AssetController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Asset::class);
    }

    /**
     * Display a data for asset create/update.
     */
    public function create(AssetCategoryQueries $assetCategoryQueries, Vendors $vendors): JsonResponse
    {
        return response()->json([
            'asset_categories' => $assetCategoryQueries->listOfAssetCategoriesQuery()->get(),
            'vendors' => $vendors->allCached(),
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(PaginateAssetsRequest $request, AssetQueries $queries): AnonymousResourceCollection
    {
        $pagination = $queries->paginateAssetsQuery($request)->apiPaginate();

        return AssetList::collection($pagination);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateAssetRequest $request, AssetEntityService $assetEntityService): JsonResponse
    {
        $resource = $assetEntityService->createAsset($request->getCreateAssetData());

        return response()->json(
            AssetWithIncludes::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Asset $asset): JsonResponse
    {
        return response()->json(
            AssetWithIncludes::make($asset)
        );
    }

    /**
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showCompaniesOfAsset(Asset $asset, CompanyQueries $companyQueries)
    {
        $this->authorize('view', $asset);

        $resource = $companyQueries->listOfAssetCompaniesQuery($asset)->get();

        return CompanyOfAsset::collection($resource);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateAssetRequest $request, AssetEntityService $assetEntityService, Asset $asset): JsonResponse
    {
        $resource = $assetEntityService->updateAsset($asset, $request->getUpdateAssetData());

        return response()->json(
            AssetWithIncludes::make($resource)
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Asset $asset, AssetEntityService $assetEntityService): JsonResponse
    {
        $assetEntityService->deleteAsset($asset);

        return response()->json(
            true
        );
    }

    public function checkUniqueness(UniquenessRequest $request, AssetQueries $assetQueries): Response
    {
        return response(
            $assetQueries->assetUniquenessQuery(
                serialNumber: $request->getSerialNumber(),
                productNumber: $request->getProductNumber(),
                ignoreModelKey: $request->getIgnoreModelKey(),
                ownerKey: $request->getOwnerKey(),
                vendorKey: $request->getVendorKey(),
            )->doesntExist()
        );
    }
}

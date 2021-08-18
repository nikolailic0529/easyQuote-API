<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\VendorRepositoryInterface as Vendors;
use App\Http\Controllers\Controller;
use App\Http\Requests\{Asset\CreateAsset, Asset\PaginateAssets, Asset\UpdateAsset};
use App\Http\Requests\Asset\Uniqueness;
use App\Http\Resources\Asset\AssetList;
use App\Http\Resources\Asset\AssetWithIncludes;
use App\Http\Resources\Company\CompanyOfAsset;
use App\Models\Asset;
use App\Queries\AssetCategoryQueries;
use App\Queries\AssetQueries;
use App\Queries\CompanyQueries;
use App\Services\Asset\AssetEntityService;
use Illuminate\Http\{JsonResponse, Resources\Json\AnonymousResourceCollection, Response};

class AssetController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Asset::class);
    }

    /**
     * Display a data for asset create/update.
     *
     * @param AssetCategoryQueries $assetCategoryQueries
     * @param Vendors $vendors
     * @return JsonResponse
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
     *
     * @param PaginateAssets $request
     * @param \App\Queries\AssetQueries $queries
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(PaginateAssets $request, AssetQueries $queries): AnonymousResourceCollection
    {
        $pagination = $queries->paginateAssetsQuery($request)->apiPaginate();

        return AssetList::collection($pagination);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CreateAsset $request
     * @param AssetEntityService $assetEntityService
     * @return JsonResponse
     */
    public function store(CreateAsset $request, AssetEntityService $assetEntityService): JsonResponse
    {
        $resource = $assetEntityService->createAsset($request->getCreateAssetData());

        return response()->json(
            AssetWithIncludes::make($resource),
            Response::HTTP_CREATED
        );
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\Asset $asset
     * @return JsonResponse
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
     *
     * @param UpdateAsset $request
     * @param AssetEntityService $assetEntityService
     * @param \App\Models\Asset $asset
     * @return JsonResponse
     */
    public function update(UpdateAsset $request, AssetEntityService $assetEntityService, Asset $asset): JsonResponse
    {
        $resource = $assetEntityService->updateAsset($asset, $request->getUpdateAssetData());

        return response()->json(
            AssetWithIncludes::make($resource)
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Asset $asset
     * @param AssetEntityService $assetEntityService
     * @return JsonResponse
     */
    public function destroy(Asset $asset, AssetEntityService $assetEntityService): JsonResponse
    {
        $assetEntityService->deleteAsset($asset);

        return response()->json(
            true
        );
    }

    /**
     * @param Uniqueness $request
     * @param AssetQueries $assetQueries
     * @return Response
     */
    public function checkUniqueness(Uniqueness $request, AssetQueries $assetQueries): Response
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

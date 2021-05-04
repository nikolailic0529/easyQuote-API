<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\AssetCategoryRepository;
use App\Contracts\Repositories\AssetRepository as Assets;
use App\Contracts\Repositories\VendorRepositoryInterface as Vendors;
use App\Http\Controllers\Controller;
use App\Http\Resources\Asset\AssetList;
use App\Queries\AssetQueries;
use App\Http\Requests\{Asset\CreateAsset, Asset\PaginateAssets, Asset\UpdateAsset};
use App\Http\Requests\Asset\Uniqueness;
use App\Http\Resources\Asset\AssetCollection;
use App\Http\Resources\Asset\AssetWithIncludes;
use App\Models\Asset;
use App\Services\Asset\AssetEntityService;
use Illuminate\Http\{JsonResponse, Request, Resources\Json\AnonymousResourceCollection, Response};

class AssetController extends Controller
{
    protected Assets $assets;

    public function __construct(Assets $assets)
    {
        $this->assets = $assets;

        $this->authorizeResource(Asset::class);
    }

    /**
     * Display a data for asset create/update.
     *
     * @param AssetCategoryRepository $assetCategories
     * @param Vendors $vendors
     * @return JsonResponse
     */
    public function create(AssetCategoryRepository $assetCategories, Vendors $vendors): JsonResponse
    {
        return response()->json([
            'asset_categories' => $assetCategories->allCached(),
            'vendors' => $vendors->allCached()
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
        $pagination = $request->transformAssetsQuery($queries->paginateAssetsQuery($request))->apiPaginate();

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
     * @return JsonResponse
     */
    public function checkUniqueness(Uniqueness $request): JsonResponse
    {
        return response()->json(
            $this->assets->checkUniqueness($request->validated())
        );
    }
}

<?php

namespace App\Http\Controllers\API;

use App\Contracts\Repositories\AssetCategoryRepository;
use App\Contracts\Repositories\AssetRepository as Assets;
use App\Contracts\Repositories\VendorRepositoryInterface as Vendors;
use App\Http\Controllers\Controller;
use App\Http\Requests\{
    Asset\CreateAsset,
    Asset\UpdateAsset,
};
use App\Http\Requests\Asset\Uniqueness;
use App\Http\Resources\Asset\Asset as AssetResource;
use App\Http\Resources\Asset\AssetCollection;
use App\Models\Asset;
use Illuminate\Http\{
    Request,
    Response,
};

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
     * @return \Illuminate\Http\Response
     */
    public function create(AssetCategoryRepository $assetCategories, Vendors $vendors)
    {
        return response()->json([
            'asset_categories' => $assetCategories->allCached(),
            'vendors'          => $vendors->allCached()
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $resource = $request->filled('search')
            ? $this->assets->search($request->search)
            : $this->assets->paginate();

        return AssetCollection::make($resource);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  CreateAsset  $request
     * @return \Illuminate\Http\Response
     */
    public function store(CreateAsset $request)
    {
        $resource = $this->assets->create($request->validated())->loadMissing('assetCategory');

        return response()->json(AssetResource::make($resource), Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function show(Asset $asset)
    {
        $asset->loadMissing('assetCategory', 'address');
        
        return response()->json(AssetResource::make($asset));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateAsset  $request
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateAsset $request, Asset $asset)
    {
        $resource = $this->assets->update($asset, $request->validated())->loadMissing('assetCategory');

        return response()->json(AssetResource::make($resource));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Asset  $asset
     * @return \Illuminate\Http\Response
     */
    public function destroy(Asset $asset)
    {
        return response()->json(
            $this->assets->delete($asset)
        );
    }

    public function checkUniqueness(Uniqueness $request)
    {
        return response()->json(
            $this->assets->checkUniqueness($request->validated())
        );
    }
}

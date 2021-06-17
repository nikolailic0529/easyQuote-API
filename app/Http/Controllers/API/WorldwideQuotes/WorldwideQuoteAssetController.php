<?php

namespace App\Http\Controllers\API\WorldwideQuotes;

use App\Contracts\Services\ProcessesWorldwideQuoteAssetState;
use App\Contracts\Services\ProcessesWorldwideQuoteState;
use App\Http\Controllers\Controller;
use App\Http\Requests\WorldwideQuote\AssetsLookup;
use App\Http\Requests\WorldwideQuote\BatchWarrantyLookup;
use App\Http\Requests\WorldwideQuote\ImportBatchAssetFile;
use App\Http\Requests\WorldwideQuote\InitializeQuoteAsset;
use App\Http\Requests\WorldwideQuote\MoveAssetsBetweenGroupsOfAssets;
use App\Http\Requests\WorldwideQuote\StoreGroupOfAssets;
use App\Http\Requests\WorldwideQuote\UpdateGroupOfAssets;
use App\Http\Requests\WorldwideQuote\UpdateQuoteAssets;
use App\Http\Requests\WorldwideQuote\UploadBatchAssetsFile;
use App\Http\Resources\WorldwideQuote\AssetLookupResult;
use App\Http\Resources\WorldwideQuote\AssetsGroup;
use App\Http\Resources\WorldwideQuote\BatchAssetLookupResult;
use App\Models\Quote\WorldwideQuote;
use App\Models\WorldwideQuoteAsset;
use App\Models\WorldwideQuoteAssetsGroup;
use App\Queries\WorldwideQuoteQueries;
use App\Services\Exceptions\ValidationException;
use App\Services\WorldwideQuote\AssetServiceLookupService;
use App\Services\WorldwideQuote\WorldwideQuoteVersionGuard;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorldwideQuoteAssetController extends Controller
{
    protected ProcessesWorldwideQuoteAssetState $processor;

    public function __construct(ProcessesWorldwideQuoteAssetState $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Initialize a new Worldwide Quote Asset.
     *
     * @param InitializeQuoteAsset $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function initializeQuoteAsset(InitializeQuoteAsset $request,
                                         WorldwideQuoteVersionGuard $versionGuard,
                                         WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $asset = $this->processor->initializeQuoteAsset(
            $version,
            $request->getInitializeAssetData()
        );

        return response()->json(
            $asset,
            Response::HTTP_CREATED
        );
    }

    /**
     * Batch update worldwide quote assets.
     *
     * @param UpdateQuoteAssets $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @param ProcessesWorldwideQuoteState $quoteProcessor
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function batchUpdateQuoteAssets(UpdateQuoteAssets $request,
                                           WorldwideQuoteVersionGuard $versionGuard,
                                           WorldwideQuote $worldwideQuote,
                                           ProcessesWorldwideQuoteState $quoteProcessor): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $this->processor->batchUpdateQuoteAssets(
            $version,
            $request->getAssetDataCollection()
        );

        $quoteProcessor
            ->setActingUser($request->user())
            ->processQuoteAssetsCreationStep(
                $version,
                $request->getStage()
            );

        return response()->noContent();
    }

    /**
     * Upload batch assets file.
     *
     * @param UploadBatchAssetsFile $request
     * @param WorldwideQuote $worldwideQuote
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function uploadBatchQuoteAssetsFile(UploadBatchAssetsFile $request, WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $result = $this->processor->readBatchAssetFile(
            $request->getFile()
        );

        return response()->json(
            $result,
            Response::HTTP_OK
        );
    }

    /**
     * Import batch assets file.
     *
     * @param ImportBatchAssetFile $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function importBatchQuoteAssetsFile(ImportBatchAssetFile $request,
                                               WorldwideQuoteVersionGuard $versionGuard,
                                               WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $this->processor->importBatchAssetFile(
            $version,
            $request->getImportData()
        );

        return response()->noContent();
    }

    /**
     * Batch warranty lookup of worldwide quote assets.
     *
     * @param BatchWarrantyLookup $request
     * @param WorldwideQuote $worldwideQuote
     * @param AssetServiceLookupService $lookupService
     * @return JsonResponse
     * @throws AuthorizationException|ValidationException
     */
    public function batchWarrantyLookup(BatchWarrantyLookup $request, WorldwideQuote $worldwideQuote, AssetServiceLookupService $lookupService): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $result = $lookupService->performBatchWarrantyLookup(
            $request->getLookupDataCollection($worldwideQuote)
        );

        $lookupService->guessServiceLevelDataOfAssetLookupResults($result);

        return response()->json(
            BatchAssetLookupResult::make($result),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the specified Worldwide Quote Asset.
     *
     * @param Request $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param WorldwideQuote $worldwideQuote
     * @param WorldwideQuoteAsset $asset
     * @return Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function destroyQuoteAsset(Request $request,
                                      WorldwideQuoteVersionGuard $versionGuard,
                                      WorldwideQuote $worldwideQuote,
                                      WorldwideQuoteAsset $asset): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $this->processor->deleteQuoteAsset(
            $version,
            $asset
        );

        return response()->noContent();
    }

    /**
     * Store a new group of assets.
     *
     * @param \App\Http\Requests\WorldwideQuote\StoreGroupOfAssets $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param \App\Contracts\Services\ProcessesWorldwideQuoteState $quoteProcessor
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function storeGroupOfAssets(StoreGroupOfAssets $request,
                                       WorldwideQuoteVersionGuard $versionGuard,
                                       ProcessesWorldwideQuoteState $quoteProcessor,
                                       WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $quoteProcessor
            ->setActingUser($request->user())
            ->createGroupOfAssets($version, $request->getAssetsGroupData());

        return response()->json(
            AssetsGroup::make($request->loadGroupAttributes($resource)),
            Response::HTTP_CREATED
        );
    }

    /**
     * Update the existing group of assets.
     *
     * @param \App\Http\Requests\WorldwideQuote\UpdateGroupOfAssets $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param \App\Contracts\Services\ProcessesWorldwideQuoteState $quoteProcessor
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Models\WorldwideQuoteAssetsGroup $assetsGroup
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateGroupOfAssets(UpdateGroupOfAssets $request,
                                        WorldwideQuoteVersionGuard $versionGuard,
                                        ProcessesWorldwideQuoteState $quoteProcessor,
                                        WorldwideQuote $worldwideQuote,
                                        WorldwideQuoteAssetsGroup $assetsGroup): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $resource = $quoteProcessor
            ->setActingUser($request->user())
            ->updateGroupOfAssets($version, $assetsGroup, $request->getAssetsGroupData());

        return response()->json(
            AssetsGroup::make($request->loadGroupAttributes($resource)),
            Response::HTTP_OK
        );
    }

    /**
     * Delete the existing group of assets.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param \App\Contracts\Services\ProcessesWorldwideQuoteState $quoteProcessor
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Models\WorldwideQuoteAssetsGroup $assetsGroup
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function deleteGroupOfAssets(Request $request,
                                        WorldwideQuoteVersionGuard $versionGuard,
                                        ProcessesWorldwideQuoteState $quoteProcessor,
                                        WorldwideQuote $worldwideQuote,
                                        WorldwideQuoteAssetsGroup $assetsGroup): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $quoteProcessor
            ->setActingUser($request->user())
            ->deleteGroupOfAssets($version, $assetsGroup);

        return response()->noContent();
    }

    /**
     * Move assets between groups of pack quote assets.
     *
     * @param \App\Http\Requests\WorldwideQuote\MoveAssetsBetweenGroupsOfAssets $request
     * @param \App\Services\WorldwideQuote\WorldwideQuoteVersionGuard $versionGuard
     * @param \App\Contracts\Services\ProcessesWorldwideQuoteState $quoteProcessor
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function moveAssetsBetweenGroupsOfAssets(MoveAssetsBetweenGroupsOfAssets $request,
                                                    WorldwideQuoteVersionGuard $versionGuard,
                                                    ProcessesWorldwideQuoteState $quoteProcessor,
                                                    WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $quoteProcessor
            ->setActingUser($request->user())
            ->moveAssetsBetweenGroupsOfAssets(
                $version,
                $request->getOutputAssetsGroup(),
                $request->getInputAssetsGroup(),
                $request->getMovedAssets()
            );

        return response()->json(
            $request->getChangedRowsGroups(),
            Response::HTTP_OK
        );
    }

    /**
     * Perform assets lookup by several fields.
     *
     * @param \App\Http\Requests\WorldwideQuote\AssetsLookup $request
     * @param \App\Models\Quote\WorldwideQuote $worldwideQuote
     * @param \App\Queries\WorldwideQuoteQueries $queries
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function performAssetsLookup(AssetsLookup $request,
                                        WorldwideQuote $worldwideQuote,
                                        WorldwideQuoteQueries $queries): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $queries->assetsLookupQuery(
            $worldwideQuote->activeVersion,
            $request->getAssetsLookupData()
        )->get();

        return response()->json(
            AssetLookupResult::collection($resource)
        );
    }
}

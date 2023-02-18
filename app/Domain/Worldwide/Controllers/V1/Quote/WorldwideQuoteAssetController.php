<?php

namespace App\Domain\Worldwide\Controllers\V1\Quote;

use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteAssetState;
use App\Domain\Worldwide\Contracts\ProcessesWorldwideQuoteState;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteAssetsGroup;
use App\Domain\Worldwide\Queries\WorldwideQuoteQueries;
use App\Domain\Worldwide\Requests\Quote\AssetsLookupRequest;
use App\Domain\Worldwide\Requests\Quote\BatchDeleteQuoteAssetRequest;
use App\Domain\Worldwide\Requests\Quote\BatchInitializeQuoteAssetRequest;
use App\Domain\Worldwide\Requests\Quote\BatchWarrantyLookupRequest;
use App\Domain\Worldwide\Requests\Quote\ImportBatchAssetFileRequest;
use App\Domain\Worldwide\Requests\Quote\InitializeQuoteAssetRequest;
use App\Domain\Worldwide\Requests\Quote\MoveAssetsBetweenGroupsOfAssetsRequest;
use App\Domain\Worldwide\Requests\Quote\ShowStateOfAssetsGroupRequest;
use App\Domain\Worldwide\Requests\Quote\StoreGroupOfAssetsRequest;
use App\Domain\Worldwide\Requests\Quote\UpdateGroupOfAssetsRequest;
use App\Domain\Worldwide\Requests\Quote\UpdateQuoteAssetsRequest;
use App\Domain\Worldwide\Requests\Quote\UploadBatchAssetsFileRequest;
use App\Domain\Worldwide\Resources\V1\Quote\AssetLookupResult;
use App\Domain\Worldwide\Resources\V1\Quote\AssetsGroup;
use App\Domain\Worldwide\Resources\V1\Quote\BatchAssetLookupResult;
use App\Domain\Worldwide\Services\WorldwideQuote\AssetServiceLookupService;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteDataMapper;
use App\Domain\Worldwide\Services\WorldwideQuote\WorldwideQuoteVersionGuard;
use App\Foundation\Http\Controller;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class WorldwideQuoteAssetController extends Controller
{
    public function __construct(protected ProcessesWorldwideQuoteAssetState $processor)
    {
    }

    /**
     * Initialize a new Worldwide Quote Asset.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function initializeQuoteAsset(InitializeQuoteAssetRequest $request,
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
     * Batch initialize quote assets.
     *
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function batchInitializeQuoteAsset(BatchInitializeQuoteAssetRequest $request,
                                              WorldwideQuoteVersionGuard $versionGuard,
                                              WorldwideQuote $worldwideQuote): JsonResponse
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        $collection = $this->processor->batchInitializeQuoteAsset(
            $version,
            $request->getInitializeAssetCollection()
        );

        return response()->json(
            $collection,
            Response::HTTP_CREATED
        );
    }

    /**
     * Batch update worldwide quote assets.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function batchUpdateQuoteAssets(UpdateQuoteAssetsRequest $request,
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
     * @throws AuthorizationException
     */
    public function uploadBatchQuoteAssetsFile(UploadBatchAssetsFileRequest $request,
                                               WorldwideQuote $worldwideQuote): JsonResponse
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function importBatchQuoteAssetsFile(ImportBatchAssetFileRequest $request,
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
     * @throws AuthorizationException|\App\Foundation\Validation\Exceptions\ValidationException
     */
    public function batchWarrantyLookup(BatchWarrantyLookupRequest $request, WorldwideQuote $worldwideQuote, AssetServiceLookupService $lookupService): JsonResponse
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
     * Batch delete assets from quote.
     *
     * @throws AuthorizationException
     * @throws \Throwable
     */
    public function batchDestroyQuoteAsset(BatchDeleteQuoteAssetRequest $request,
                                           WorldwideQuoteVersionGuard $versionGuard,
                                           WorldwideQuote $worldwideQuote): Response
    {
        $this->authorize('update', $worldwideQuote);

        $version = $versionGuard->resolveModelForActingUser($worldwideQuote, $request->user());

        foreach ($request->getAssetModels() as $asset) {
            $this->processor->deleteQuoteAsset(
                $version,
                $asset
            );
        }

        return response()->noContent();
    }

    /**
     * Store a new group of assets.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function storeGroupOfAssets(StoreGroupOfAssetsRequest $request,
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
     * Show the specified group of assets.
     *
     * @throws AuthorizationException
     */
    public function showGroupOfAssets(ShowStateOfAssetsGroupRequest $request,
                                      WorldwideQuote $worldwideQuote,
                                      WorldwideQuoteAssetsGroup $assetsGroup): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        return response()->json(
            AssetsGroup::make($request->loadGroupAttributes($assetsGroup))
        );
    }

    /**
     * Update the existing group of assets.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function updateGroupOfAssets(UpdateGroupOfAssetsRequest $request,
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
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     */
    public function moveAssetsBetweenGroupsOfAssets(MoveAssetsBetweenGroupsOfAssetsRequest $request,
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
     * @throws AuthorizationException
     */
    public function performAssetsLookup(AssetsLookupRequest $request,
                                        WorldwideQuote $worldwideQuote,
                                        WorldwideQuoteQueries $queries,
                                        WorldwideQuoteDataMapper $dataMapper): JsonResponse
    {
        $this->authorize('view', $worldwideQuote);

        $resource = $queries->assetsLookupQuery(
            quoteVersion: $worldwideQuote->activeVersion,
            data: $request->getAssetsLookupData()
        )->get();

        $dataMapper->markExclusivityOfWorldwidePackQuoteAssetsForCustomer(quote: $worldwideQuote, assets: $resource);
        $dataMapper->includeContractDurationToPackAssets(quote: $worldwideQuote, assets: $resource);

        return response()->json(
            AssetLookupResult::collection($resource)
        );
    }
}

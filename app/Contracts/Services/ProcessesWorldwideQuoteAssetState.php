<?php

namespace App\Contracts\Services;

use App\DTO\WorldwideQuote\ImportBatchAssetFileData;
use App\DTO\WorldwideQuote\InitializeWorldwideQuoteAssetData;
use App\DTO\WorldwideQuote\ReadBatchFileResult;
use App\DTO\WorldwideQuote\WorldwideQuoteAssetDataCollection;
use App\Models\Quote\WorldwideQuote;
use App\Models\Quote\WorldwideQuoteVersion;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Http\UploadedFile;

interface ProcessesWorldwideQuoteAssetState
{
    /**
     * Initialize a new Worldwide Quote Asset.
     *
     * @param WorldwideQuoteVersion $quote
     * @param InitializeWorldwideQuoteAssetData $data
     * @return WorldwideQuoteAsset
     */
    public function initializeQuoteAsset(WorldwideQuoteVersion $quote, InitializeWorldwideQuoteAssetData $data): WorldwideQuoteAsset;

    /**
     * Batch update the Worldwide Quote Assets.
     *
     * @param WorldwideQuoteVersion $quote
     * @param WorldwideQuoteAssetDataCollection $collection
     * @return mixed
     */
    public function batchUpdateQuoteAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetDataCollection $collection);

    /**
     * Read headers & first rows from the batch asset file.
     *
     * @param UploadedFile $file
     * @return ReadBatchFileResult
     */
    public function readBatchAssetFile(UploadedFile $file): ReadBatchFileResult;

    /**
     * Import the batch asset file.
     *
     * @param WorldwideQuoteVersion $quote
     * @param ImportBatchAssetFileData $data
     * @return mixed
     */
    public function importBatchAssetFile(WorldwideQuoteVersion $quote, ImportBatchAssetFileData $data);

    /**
     * Delete the specified Worldwide Quote Asset.
     *
     * @param WorldwideQuoteVersion $quote
     * @param WorldwideQuoteAsset $asset
     */
    public function deleteQuoteAsset(WorldwideQuoteVersion $quote, WorldwideQuoteAsset $asset): void;
}

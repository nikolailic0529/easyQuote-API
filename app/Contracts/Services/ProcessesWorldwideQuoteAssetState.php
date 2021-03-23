<?php

namespace App\Contracts\Services;

use App\DTO\WorldwideQuote\ImportBatchAssetFileData;
use App\DTO\WorldwideQuote\ReadBatchFileResult;
use App\DTO\WorldwideQuote\WorldwideQuoteAssetDataCollection;
use App\Models\Quote\WorldwideQuote;
use App\Models\WorldwideQuoteAsset;
use Illuminate\Http\UploadedFile;

interface ProcessesWorldwideQuoteAssetState
{
    /**
     * Initialize a new Worldwide Quote Asset.
     *
     * @param WorldwideQuote $quote
     * @return WorldwideQuoteAsset
     */
    public function initializeQuoteAsset(WorldwideQuote $quote): WorldwideQuoteAsset;

    /**
     * Batch update the Worldwide Quote Assets.
     *
     * @param WorldwideQuoteAssetDataCollection $collection
     * @return mixed
     */
    public function batchUpdateQuoteAssets(WorldwideQuoteAssetDataCollection $collection);

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
     * @param ImportBatchAssetFileData $data
     * @param WorldwideQuote $worldwideQuote
     * @return mixed
     */
    public function importBatchAssetFile(ImportBatchAssetFileData $data, WorldwideQuote $worldwideQuote);

    /**
     * Delete the specified Worldwide Quote Asset.
     *
     * @param WorldwideQuoteAsset $asset
     */
    public function deleteQuoteAsset(WorldwideQuoteAsset $asset): void;
}

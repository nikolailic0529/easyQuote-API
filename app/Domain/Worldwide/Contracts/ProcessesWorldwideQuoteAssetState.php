<?php

namespace App\Domain\Worldwide\Contracts;

use App\Domain\Worldwide\DataTransferObjects\Quote\ImportBatchAssetFileData;
use App\Domain\Worldwide\DataTransferObjects\Quote\InitializeWorldwideQuoteAssetCollection;
use App\Domain\Worldwide\DataTransferObjects\Quote\InitializeWorldwideQuoteAssetData;
use App\Domain\Worldwide\DataTransferObjects\Quote\ReadBatchFileResult;
use App\Domain\Worldwide\DataTransferObjects\Quote\WorldwideQuoteAssetDataCollection;
use App\Domain\Worldwide\Models\WorldwideQuoteAsset;
use App\Domain\Worldwide\Models\WorldwideQuoteVersion;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;

interface ProcessesWorldwideQuoteAssetState
{
    /**
     * Initialize a new Worldwide Quote Asset.
     */
    public function initializeQuoteAsset(WorldwideQuoteVersion $quote, InitializeWorldwideQuoteAssetData $data): WorldwideQuoteAsset;

    /**
     * Initialize a new Worldwide Quote Asset from a collection.
     */
    public function batchInitializeQuoteAsset(WorldwideQuoteVersion $quote, InitializeWorldwideQuoteAssetCollection $collection): Collection;

    /**
     * Batch update the Worldwide Quote Assets.
     *
     * @return mixed
     */
    public function batchUpdateQuoteAssets(WorldwideQuoteVersion $quote, WorldwideQuoteAssetDataCollection $collection);

    /**
     * Recalculate exchange rate of quote assets, when quote currency was changed.
     */
    public function recalculateExchangeRateOfQuoteAssets(WorldwideQuoteVersion $quote): void;

    /**
     * Read headers & first rows from the batch asset file.
     */
    public function readBatchAssetFile(UploadedFile $file): ReadBatchFileResult;

    /**
     * Import the batch asset file.
     *
     * @return mixed
     */
    public function importBatchAssetFile(WorldwideQuoteVersion $quote, ImportBatchAssetFileData $data);

    /**
     * Delete the specified Worldwide Quote Asset.
     */
    public function deleteQuoteAsset(WorldwideQuoteVersion $quote, WorldwideQuoteAsset $asset): void;
}

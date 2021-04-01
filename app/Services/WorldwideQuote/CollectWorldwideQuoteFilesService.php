<?php

namespace App\Services\WorldwideQuote;

use App\Foundation\TemporaryDirectory;
use App\Models\Quote\WorldwideQuote;
use App\Models\QuoteFile\QuoteFile;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;
use Webpatser\Uuid\Uuid;
use ZipArchive;

class CollectWorldwideQuoteFilesService
{
    protected Filesystem $storage;

    public function __construct(Filesystem $storage)
    {
        $this->storage = $storage;
    }

    private function zipFiles(string $zipFileName, Collection $quoteFiles): \SplFileInfo
    {
        if ($quoteFiles->isEmpty()) {

            $splFile = new \SplFileObject($zipFileName, 'w+');

            $splFile->fwrite(base64_decode("UEsFBgAAAAAAAAAAAAAAAAAAAAAAAA==")); // bytes of empty zip file

            return $splFile->getFileInfo();

        }

        $zip = new ZipArchive();

        $zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $localFileNameResolver = function (string $fileName): string {
            static $takenFileNames = [];

            $localFileName = $fileName;

            if (isset($takenFileNames[$fileName])) {
                $pathInfo = pathinfo($fileName);

                $localFileName = sprintf('%s-%s.%s', $pathInfo['basename'], $takenFileNames[$fileName], $pathInfo['extension']);
            }

            $takenFileNames[$fileName] ??= 1;
            $takenFileNames[$fileName]++;

            return $localFileName;
        };

        foreach ($quoteFiles as $quoteFile) {
            if (false === $this->storage->exists($quoteFile->original_file_path)) {
                continue;
            }

            $localFileName = $localFileNameResolver($quoteFile->original_file_name ?? (string)Uuid::generate(4));

            $zip->addFile($this->storage->path($quoteFile->original_file_path), $localFileName);
        }

        $zip->close();

        return new \SplFileInfo($zipFileName);
    }

    /**
     * @param WorldwideQuote $worldwideQuote
     * @return \SplFileInfo
     * @throws \Exception
     */
    public function collectScheduleFilesFromQuote(WorldwideQuote $worldwideQuote): \SplFileInfo
    {
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $zipFileName = $temporaryDirectory->path(sprintf('%s-payment-schedule-files.zip', $worldwideQuote->quote_number));

        $distributorFileModelKeys = $worldwideQuote->activeVersion->worldwideDistributions()->whereNotNull('schedule_file_id')->pluck('schedule_file_id');

        /** @var Collection<QuoteFile>|QuoteFile[] $quoteFiles */
        $quoteFiles = QuoteFile::query()->select(['id', 'original_file_path', 'original_file_name'])
            ->whereKey($distributorFileModelKeys)
            ->get();

        return $this->zipFiles($zipFileName, $quoteFiles);
    }

    /**
     * @param WorldwideQuote $worldwideQuote
     * @return \SplFileInfo
     * @throws \Exception
     */
    public function collectDistributorFilesFromQuote(WorldwideQuote $worldwideQuote): \SplFileInfo
    {
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $zipFileName = $temporaryDirectory->path(sprintf('%s-distributor-files.zip', $worldwideQuote->quote_number));

        $distributorFileModelKeys = $worldwideQuote->activeVersion->worldwideDistributions()->whereNotNull('distributor_file_id')->pluck('distributor_file_id');

        /** @var Collection<QuoteFile>|QuoteFile[] $quoteFiles */
        $quoteFiles = QuoteFile::query()->select(['id', 'original_file_path', 'original_file_name'])
            ->whereKey($distributorFileModelKeys)
            ->get();

        return $this->zipFiles($zipFileName, $quoteFiles);
    }

}

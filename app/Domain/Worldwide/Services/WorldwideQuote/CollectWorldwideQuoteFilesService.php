<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\Worldwide\Events\Quote\WorldwideQuoteFilesExported;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Filesystem\TemporaryDirectory;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Events\Dispatcher as EventDispatcher;
use Webpatser\Uuid\Uuid;

class CollectWorldwideQuoteFilesService
{
    public function __construct(protected Filesystem $storage,
                                protected EventDispatcher $eventDispatcher)
    {
    }

    private function zipFiles(string $zipFileName, Collection $quoteFiles): \SplFileInfo
    {
        if ($quoteFiles->isEmpty()) {
            $splFile = new \SplFileObject($zipFileName, 'w+');

            $splFile->fwrite(base64_decode('UEsFBgAAAAAAAAAAAAAAAAAAAAAAAA==')); // bytes of empty zip file

            return $splFile->getFileInfo();
        }

        $zip = new \ZipArchive();

        $zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $localFileNameResolver = function (string $fileName): string {
            static $takenFileNames = [];

            $localFileName = $fileName;

            if (isset($takenFileNames[$fileName])) {
                $pathInfo = pathinfo($fileName);

                $localFileName = sprintf('%s-%s.%s', $pathInfo['basename'], $takenFileNames[$fileName], $pathInfo['extension']);
            }

            $takenFileNames[$fileName] ??= 1;
            ++$takenFileNames[$fileName];

            return $localFileName;
        };

        foreach ($quoteFiles as $quoteFile) {
            if (false === $this->storage->exists($quoteFile->original_file_path)) {
                continue;
            }

            $localFileName = $localFileNameResolver($quoteFile->original_file_name ?? (string) Uuid::generate(4));

            $zip->addFile($this->storage->path($quoteFile->original_file_path), $localFileName);
        }

        $zip->close();

        return new \SplFileInfo($zipFileName);
    }

    /**
     * @throws \Exception
     */
    public function collectScheduleFilesFromQuote(WorldwideQuote $worldwideQuote): \SplFileInfo
    {
        $temporaryDirectory = (new TemporaryDirectory())->create();

        $zipFileName = $temporaryDirectory->path(sprintf('%s-payment-schedule-files.zip', $worldwideQuote->quote_number));

        $distributorFileModelKeys = $worldwideQuote->activeVersion->worldwideDistributions()->whereNotNull('schedule_file_id')->pluck('schedule_file_id');

        /** @var Collection<\App\Domain\QuoteFile\Models\QuoteFile>|\App\Domain\QuoteFile\Models\QuoteFile[] $quoteFiles */
        $quoteFiles = QuoteFile::query()->select(['id', 'original_file_path', 'original_file_name'])
            ->whereKey($distributorFileModelKeys)
            ->get();

        return tap($this->zipFiles($zipFileName, $quoteFiles), function () use ($worldwideQuote, $quoteFiles) {
            $this->eventDispatcher->dispatch(new WorldwideQuoteFilesExported($worldwideQuote, $quoteFiles));
        });
    }

    /**
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

        return tap($this->zipFiles($zipFileName, $quoteFiles), function () use ($worldwideQuote, $quoteFiles) {
            $this->eventDispatcher->dispatch(new WorldwideQuoteFilesExported($worldwideQuote, $quoteFiles));
        });
    }
}

<?php

namespace App\Domain\Worldwide\Services\WorldwideQuote;

use App\Domain\Attachment\DataTransferObjects\CreateAttachmentData;
use App\Domain\Attachment\Enum\AttachmentType;
use App\Domain\Attachment\Services\AttachmentEntityService;
use App\Domain\Authentication\Contracts\CauserAware;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Services\QuoteFileFilesystem;
use App\Domain\Worldwide\Models\WorldwideDistribution;
use App\Domain\Worldwide\Models\WorldwideQuote;
use App\Foundation\Filesystem\BinaryFileContent;
use App\Foundation\Filesystem\TemporaryDirectory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Webpatser\Uuid\Uuid;

class WorldwideQuoteAttachmentService implements CauserAware
{
    protected ?Model $causer = null;

    public function __construct(
        protected readonly WorldwideQuoteExporter $quoteExporter,
        protected readonly WorldwideQuoteDataMapper $dataMapper,
        protected readonly AttachmentEntityService $attachmentEntityService,
        protected readonly QuoteFileFilesystem $quoteFileFilesystem,
        protected readonly LoggerInterface $logger,
    ) {
    }

    public function createAttachmentFromSubmittedQuote(WorldwideQuote $quote): void
    {
        $result = $this->quoteExporter->export(
            previewData: $this->dataMapper->mapWorldwideQuotePreviewDataForExport($quote),
            exportedEntity: $quote,
        );

        $this->attachmentEntityService
            ->setCauser($this->causer)
            ->createAttachmentForEntity(
                new CreateAttachmentData(
                    file: new BinaryFileContent($result->content, $result->filename),
                    type: AttachmentType::SubmittedQuote,
                    isDeleteProtected: true,
                ),
                $quote->opportunity,
            );
    }

    public function createAttachmentFromDistributorFiles(WorldwideQuote $quote): void
    {
        if ($quote->contractType()->getParentKey() !== CT_CONTRACT) {
            return;
        }

        /** @var Collection $files */
        $files = $quote->activeVersion
            ->worldwideDistributions()
            ->lazyById(10)
            ->reduce(function (Collection $files, WorldwideDistribution $distributorQuote): Collection {
                if (null !== $distributorQuote->distributorFile) {
                    $files->push($distributorQuote->distributorFile);
                }

                if (null !== $distributorQuote->scheduleFile) {
                    $files->push($distributorQuote->scheduleFile);
                }

                return $files;
            }, Collection::empty());

        $files = $files->filter(function (QuoteFile $file) use ($quote): bool {
            if ($this->quoteFileFilesystem->exists($file->original_file_path)) {
                return true;
            }

            $this->logger->warning('Could not create attachment from the file.', [
                'quote_id' => $quote->getKey(),
                'quote_number' => $quote->quote_number,
                'quote_file_id' => $file->getKey(),
                'original_file_path' => $file->original_file_path,
            ]);

            return false;
        })
            ->values();

        if ($files->isEmpty()) {
            return;
        }

        if ($files->containsOneItem()) {
            /** @var \App\Domain\QuoteFile\Models\QuoteFile $file */
            $file = $files->first();

            $fileContent = new BinaryFileContent(
                file_get_contents($this->quoteFileFilesystem->path($file->original_file_path)),
                $file->original_file_name
            );

            $this->attachmentEntityService
                ->setCauser($this->causer)
                ->createAttachmentForEntity(
                    new CreateAttachmentData(
                        file: $fileContent,
                        type: AttachmentType::DistributionQuotation,
                        isDeleteProtected: true,
                    ),
                    $quote->opportunity,
                );

            return;
        }

        $tmp = (new TemporaryDirectory())->create();
        $zipFilePath = $tmp->path(Str::random().'.zip');
        $zip = new \ZipArchive();
        $zip->open($zipFilePath, flags: \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        $zipEntryNameResolver = static function (string $fileName): string {
            static $cache = [];

            $localFileName = $fileName;

            if (isset($cache[$fileName])) {
                $pathInfo = pathinfo($fileName);

                $localFileName = sprintf('%s-%s.%s', $pathInfo['basename'], $cache[$fileName], $pathInfo['extension']);
            }

            $cache[$fileName] ??= 1;
            ++$cache[$fileName];

            return $localFileName;
        };

        $files->each(function (QuoteFile $file) use ($zipEntryNameResolver, $zip): void {
            $entryName = $zipEntryNameResolver($file->original_file_name ?? Uuid::generate(4)->string);

            $zip->addFile($this->quoteFileFilesystem->path($file->original_file_path), $entryName);
        });

        $zip->close();

        $fileContent = new BinaryFileContent(
            file_get_contents($zipFilePath),
            sprintf('%s-distributor-files.zip', $quote->quote_number)
        );

        $this->attachmentEntityService
            ->setCauser($this->causer)
            ->createAttachmentForEntity(
                new CreateAttachmentData(
                    file: $fileContent,
                    type: AttachmentType::DistributionQuotation,
                    isDeleteProtected: true,
                ),
                $quote->opportunity,
            );
    }

    public function setCauser(?Model $causer): static
    {
        return tap($this, fn () => $this->causer = $causer);
    }
}

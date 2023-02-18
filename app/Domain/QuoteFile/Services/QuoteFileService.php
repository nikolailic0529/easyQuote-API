<?php

namespace App\Domain\QuoteFile\Services;

use App\Domain\DocumentProcessing\Readers\Csv\CsvParser;
use App\Domain\QuoteFile\Imports\CountPages as ExcelPagesCounter;
use App\Domain\QuoteFile\Models\DataSelectSeparator;
use App\Domain\QuoteFile\Models\QuoteFile;
use App\Domain\QuoteFile\Models\QuoteFileFormat;
use App\Domain\QuoteFile\Resources\V1\DownloadableQuoteFile;
use App\Domain\Rescue\Events\Quote\RescueQuoteFileExported;
use App\Domain\Rescue\Models\Quote;
use App\Domain\User\Models\User;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Smalot\PdfParser\Parser as PDFParser;

class QuoteFileService
{
    public function __construct(protected EventDispatcher $eventDispatcher)
    {
    }

    public function storeQuoteFile(UploadedFile $file, User $user, string $fileType): QuoteFile
    {
        $filePath = $file->store($user->quoteFilesDirectory);

        $format = $this->determineFileFormat($filePath);
        $pageCount = $this->countPages(Storage::path($filePath));

        $quoteFile = new QuoteFile([
            'pages' => $pageCount,
            'original_file_path' => $filePath,
            'original_file_name' => $file->getClientOriginalName(),
            'file_type' => $fileType,
        ]);

        $quoteFile->format()->associate($format);

        if ($format->extension === 'csv') {
            $delimiter = (new CsvParser())->guessDelimiter(Storage::path($filePath));

            $delimiter = DataSelectSeparator::where('name', $delimiter)->first();

            $quoteFile->dataSelectSeparator()->associate($delimiter);
        }

        return tap($quoteFile, function (QuoteFile $quoteFile) {
            $quoteFile->save();
        });
    }

    public function countPages(string $filePath): int
    {
        $extension = File::extension($filePath);

        if ($extension === 'pdf') {
            $details = (new PDFParser())->parseFile($filePath)->getDetails();

            return (int) $details['Pages'];
        }

        if (in_array($extension, ['xls', 'xlsx'])) {
            $import = new ExcelPagesCounter();

            Excel::import($import, $filePath);

            return (int) $import->getSheetCount();
        }

        if (in_array($extension, ['csv', 'txt', 'docx'])) {
            return 1;
        }

        throw new \ValueError("Unsupported file extension: $extension");
    }

    /**
     * Determine Quote File Format.
     */
    public function determineFileFormat(string $filePath): ?QuoteFileFormat
    {
        $extension = File::extension($filePath);
        $extensions = Arr::wrap($extension);

        if ($extension === 'txt') {
            $extensions[] = 'csv';
        }

        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return QuoteFileFormat::query()->whereIn('extension', $extensions)->first();
    }

    public function downloadQuoteFile(Quote $quote, string $fileType): DownloadableQuoteFile
    {
        $relation = match ($fileType) {
            'price' => 'priceList',
            'schedule' => 'paymentSchedule',
            default => throw new \ValueError("Unsupported file type: $fileType"),
        };

        /** @var QuoteFile|null */
        $quoteFile = $quote->activeVersionOrCurrent->getRelationValue($relation);

        if (is_null($quoteFile) || !$quoteFile->exists) {
            throw (new ModelNotFoundException())->setModel(QuoteFile::class);
        }

        $this->eventDispatcher->dispatch(new RescueQuoteFileExported($quoteFile, $quote));

        return new DownloadableQuoteFile(
            filePath: Storage::path($quoteFile->original_file_path),
            fileName: $quoteFile->original_file_name
        );
    }
}

<?php

namespace App\Services;

use App\Http\Resources\DownloadableQuoteFile;
use App\Imports\CountPages as ExcelPagesCounter;
use App\Models\Quote\Quote;
use App\Models\QuoteFile\DataSelectSeparator;
use App\Models\QuoteFile\QuoteFile;
use App\Models\QuoteFile\QuoteFileFormat;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Smalot\PdfParser\Parser as PDFParser;
use ValueError;

class QuoteFileService
{
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
            $delimiter = (new CsvParser)->guessDelimiter(Storage::path($filePath));

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
            $details = (new PDFParser)->parseFile($filePath)->getDetails();

            return (int) $details['Pages'];
        }

        if (in_array($extension, ['xls', 'xlsx'])) {
            $import = new ExcelPagesCounter;

            Excel::import($import, $filePath);

            return (int) $import->getSheetCount();
        }

        if (in_array($extension, ['csv', 'txt', 'docx'])) {
            return 1;
        }

        throw new ValueError("Unsupported file extension: $extension");
    }

    /**
     * Determine Quote File Format.
     *
     * @param string $filePath
     * @return QuoteFileFormat|null
     */
    public function determineFileFormat(string $filePath)
    {
        $extension = File::extension($filePath);
        $extensions = Arr::wrap($extension);

        if ($extension === 'txt') {
            $extensions[] = 'csv';
        }

        return QuoteFileFormat::whereIn('extension', $extensions)->first();
    }

    public function downloadQuoteFile(Quote $quote, string $fileType): DownloadableQuoteFile
    {
        $relation = [
            'price' => 'priceList',
            'schedule' => 'paymentSchedule',
        ][$fileType] ?? null;

        if (is_null($relation)) {
            throw new ValueError("Unsupported file type: $fileType");
        }

        /** @var \App\Models\QuoteFile\QuoteFile|null */
        $quoteFile = $quote->activeVersionOrCurrent->getRelationValue($relation);

        if (is_null($quoteFile) || !$quoteFile->exists) {
            throw (new ModelNotFoundException)->setModel(QuoteFile::class);
        }

        return new DownloadableQuoteFile(
            Storage::path($quoteFile->original_file_path),
            $quoteFile->original_file_name
        );
    }
}

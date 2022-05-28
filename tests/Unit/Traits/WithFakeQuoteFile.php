<?php

namespace Tests\Unit\Traits;

use App\Contracts\Services\{
    ManagesDocumentProcessors,
    PdfParserInterface,
    WordParserInterface
};
use App\Models\{
    Quote\Quote,
    QuoteFile\QuoteFile
};
use App\Services\QuoteFileService;
use Illuminate\Support\{
    Str,
    Facades\File,
    Facades\Storage,
    Facades\DB
};
use SplFileInfo;

trait WithFakeQuoteFile
{
    /**
     * Main Parser Service.
     *
     * @var ManagesDocumentProcessors
     */
    protected $parser;

    /**
     * PdfParser Wrapper.
     *
     * @var PdfParser
     */
    protected $pdfParser;

    /**
     * WordParser wrapper.
     *
     * @var WordParser
     */
    protected $wordParser;

    /**
     * QuoteFile Repository.
     *
     * @var QuoteFileRepository
     */
    protected $quoteFileRepository;

    protected function setUpFakeQuoteFile()
    {
        $this->parser = app(ManagesDocumentProcessors::class);
        $this->pdfParser = app(PdfParserInterface::class);
        $this->wordParser = app(WordParserInterface::class);
        $this->quoteFileRepository = app('quotefile.repository');
    }

    protected function createQuoteFile(string $relativePath, Quote $quote, string $fileType = 'Distributor Price List'): QuoteFile
    {
        Storage::persistentFake();

        $originalFileName = File::name($relativePath);
        $extension = File::extension($relativePath);
        $filename = Str::random(40) . '.' . File::extension($relativePath);
        $filePath =  "{$quote->user->quoteFilesDirectory}/{$filename}";

        Storage::makeDirectory($quote->user->quoteFilesDirectory);

        File::copy(base_path($relativePath), Storage::path($filePath));

        $quoteFile = $quote->user->quoteFiles()->create([
            'quote_id'              => $quote->id,
            'original_file_path'    => $filePath,
            'quote_file_format_id'  => $this->determineFileFormat($extension),
            'file_type'             => $fileType,
            'pages'                 => (new QuoteFileService)->countPages(base_path($relativePath)),
            'imported_page'         => 1,
            'original_file_name'    => $originalFileName
        ]);

        $quote->priceList()->associate($quoteFile)->save();

        return $quoteFile;
    }

    protected function createFakeQuoteFile(Quote $quote): QuoteFile
    {
        $quoteFile = $quote->user->quoteFiles()->create([
            'original_file_path'    => Str::random(40) . '.pdf',
            'quote_file_format_id'  => DB::table('quote_file_formats')->where('extension', 'pdf')->value('id'),
            'file_type'             => 'Distributor Price List',
            'pages'                 => 2,
            'imported_page'         => 1,
            'original_file_name'    => Str::random(40) . '.pdf'
        ]);

        $quote->priceList()->associate($quoteFile)->save();

        return $quoteFile;
    }

    /**
     * Determine File Format.
     *
     * @param SplFileInfo|string $file
     * @return string
     */
    protected function determineFileFormat($file): string
    {
        $extensions = $file instanceof SplFileInfo
            ? collect($file->getExtension())
            : collect($file);

        if ($extensions->first() === 'txt') {
            $extensions->push('csv');
        }

        return DB::table('quote_file_formats')->whereIn('extension', $extensions->toArray())->value('id');
    }
}

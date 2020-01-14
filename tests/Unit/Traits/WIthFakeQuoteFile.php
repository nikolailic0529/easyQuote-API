<?php

namespace Tests\Unit\Traits;

use App\Contracts\Services\{
    ParserServiceInterface,
    PdfParserInterface,
    WordParserInterface
};
use App\Models\{
    Quote\Quote,
    QuoteFile\QuoteFile
};
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
     * @var \App\Services\ParserService
     */
    protected $parser;

    /**
     * PdfParser Wrapper.
     *
     * @var \App\Services\PdfParser\PdfParser
     */
    protected $pdfParser;

    /**
     * WordParser wrapper.
     *
     * @var \App\Services\WordParser
     */
    protected $wordParser;

    /**
     * QuoteFile Repository.
     *
     * @var \App\Repositories\QuoteFile\QuoteFileRepository
     */
    protected $quoteFileRepository;

    protected function setUpFakeQuoteFile()
    {
        $this->parser = app(ParserServiceInterface::class);
        $this->pdfParser = app(PdfParserInterface::class);
        $this->wordParser = app(WordParserInterface::class);
        $this->quoteFileRepository = app('quotefile.repository');
    }

    protected function createQuoteFile(string $relativePath, Quote $quote, string $fileType = 'Distributor Price List'): QuoteFile
    {
        $originalFileName = File::name($relativePath);
        $extension = File::extension($relativePath);
        $filename = Str::random(40) . '.' . File::extension($relativePath);
        $filepath = "{$quote->user->quoteFilesDirectory}/{$filename}";

        Storage::makeDirectory($quote->user->quoteFilesDirectory);

        File::copy(base_path($relativePath), Storage::path($filepath));

        $quoteFile = $quote->user->quoteFiles()->create([
            'quote_id' => $quote->id,
            'original_file_path' => Storage::path($filepath),
            'quote_file_format_id' => $this->determineFileFormat($extension),
            'file_type' => $fileType,
            'pages' => $this->parser->countPages($filepath, true),
            'imported_page' => 1,
            'original_file_name' => $originalFileName
        ]);

        $this->preHandle($quoteFile);

        return $quoteFile;
    }

    protected function createFakeQuoteFile(Quote $quote): QuoteFile
    {
        return $quote->user->quoteFiles()->create([
            'quote_id' => $quote->id,
            'original_file_path' => Str::random(40) . '.pdf',
            'quote_file_format_id' => DB::table('quote_file_formats')->where('extension', 'pdf')->value('id'),
            'file_type' => 'Distributor Price List',
            'pages' => 2,
            'imported_page' => 1,
            'original_file_name' => Str::random(40) . '.pdf'
        ]);
    }

    protected function preHandle(QuoteFile $quoteFile): void
    {
        switch ($quoteFile->format->extension) {
            case 'pdf':
                $text = $this->pdfParser->getText($quoteFile->original_file_path, false);
                $this->quoteFileRepository->createRawData($quoteFile, $text);
                break;
            case 'docx':
            case 'doc':
                $text = $this->wordParser->getText($quoteFile->original_file_path, false);
                $this->quoteFileRepository->createRawData($quoteFile, $text);
                break;
        }

        if ($quoteFile->isCsv()) {
            $quoteFile->fullPath = true;
        }
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

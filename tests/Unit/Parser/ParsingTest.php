<?php

namespace Tests\Unit\Parser;

use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Contracts\Services\{
    ParserServiceInterface,
    PdfParserInterface,
    WordParserInterface
};
use App\Models\{
    User,
    QuoteFile\QuoteFile
};
use Tests\TestCase;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Foundation\Testing\{
    WithFaker,
    DatabaseTransactions
};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use \File;
use Tests\Unit\Traits\{
    FakeQuote,
    FakeUser
};

abstract class ParsingTest extends TestCase
{
    use DatabaseTransactions, WithFaker, FakeUser, FakeQuote;

    protected $parser;

    protected $wordParser;

    protected $pdfParser;

    protected $quoteFileRepository;

    protected $user;

    protected $quote;

    protected $mapping;

    public function setUp(): void
    {
        parent::setUp();

        $this->parser = app(ParserServiceInterface::class);
        $this->wordParser = app(WordParserInterface::class);
        $this->pdfParser = app(PdfParserInterface::class);
        $this->quoteFileRepository = app(QuoteFileRepositoryInterface::class);

        $this->user = $this->fakeUser();
        $this->quote = $this->fakeQuote($this->user);
        $this->mapping = $this->mapping();
    }

    public function message(QuoteFile $quoteFile)
    {
        $exception = filled($quoteFile->exception) ? "with Exception: {$quoteFile->exception}" : "without Exception";

        return "Parsing is failed {$exception} on \nFile: {$quoteFile->original_file_path}";
    }

    protected function fakeQuoteFiles(string $country)
    {
        $quoteFiles = collect($this->filesList($country))->map(function ($file) use ($country) {
            return $this->user->quoteFiles()->make([
                'user_id' => $this->user->id,
                'original_file_path' => $file->getRealPath(),
                'quote_file_format_id' => $this->determineFileFormat($file),
                'file_type' => $this->filesType(),
                'pages' => $this->parser->countPages($file->getRealPath(), false),
                'imported_page' => $this->getMappingAttribute('page', $file->getFilename()),
                'original_file_name' => $file->getFilename()
            ]);
        });

        $this->quote->quoteFiles()->saveMany($quoteFiles);

        $quoteFiles->each(function ($quoteFile) {
            $this->preHanlde($quoteFile);
        });

        return $quoteFiles;
    }

    /**
     * Process Files by specified Country.
     *
     * @return void
     */
    protected function processFilesByCountry(string $country)
    {
        $quoteFiles = $this->fakeQuoteFiles($country);

        $quoteFiles->each(function ($quoteFile) use ($country) {
            $this->parser->routeParser($quoteFile);
            $this->performFileAssertions($quoteFile);
        });
    }

    protected function filesList(string $country)
    {
        $filesPath = "{$this->filesDirPath()}/{$country}";

        return File::files($filesPath);
    }

    protected function determineFileFormat(SplFileInfo $file)
    {
        $extensions = collect($file->getExtension());

        if ($extensions->first() === 'txt') {
            $extensions->push('csv');
        }

        return DB::table('quote_file_formats')->whereIn('extension', $extensions->toArray())->value('id');
    }

    protected function preHanlde(QuoteFile $quoteFile)
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

    protected function getMappingAttribute(string $attribute, string $filename)
    {
        return data_get($this->mapping->collapse()->firstWhere('filename', '===', $filename), $attribute);
    }

    /**
     * Get Mapping for specified Parsing.
     *
     * @return \Illuminate\Support\Collection
     */
    abstract protected function mapping(): Collection;

    /**
     * Specified Directory with Files.
     *
     * @return string
     */
    abstract protected function filesDirPath(): string;

    /**
     * Testing Files Type ("Payment Schedule" | "Distributor Price List")
     *
     * @return string
     */
    abstract protected function filesType(): string;

    /**
     * Perform specified assertions on each parsed file.
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    abstract protected function performFileAssertions(QuoteFile $quoteFile): void;
}

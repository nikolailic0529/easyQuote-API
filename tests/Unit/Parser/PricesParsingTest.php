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
    Vendor,
    Company,
    Data\Timezone,
    Data\Country,
    QuoteFile\QuoteFile,
    QuoteFile\QuoteFileFormat,
    QuoteTemplate\QuoteTemplate
};
use Tests\TestCase;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use \File;

abstract class PricesParsingTest extends TestCase
{
    use DatabaseTransactions;

    protected $parser;

    protected $wordParser;

    protected $pdfParser;

    protected $quoteFileRepository;

    protected $user;

    protected $quote;

    protected $quoteFile;

    public function setUp(): void
    {
        parent::setUp();

        $this->parser = app(ParserServiceInterface::class);
        $this->wordParser = app(WordParserInterface::class);
        $this->pdfParser = app(PdfParserInterface::class);
        $this->quoteFileRepository = app(QuoteFileRepositoryInterface::class);

        $this->user = $this->fakeUser();
        $this->quote = $this->fakeQuote($this->user);
        $this->quoteFiles = $this->fakeQuoteFiles();
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testPricesProcessing()
    {
        $mapping = collect(json_decode(file_get_contents('tests/Unit/Parser/data/prices/mapping.json'), true));
        $mapping = collect($mapping->get($this->countryName()));

        $this->quoteFiles->each(function ($quoteFile) use ($mapping) {
            $this->parser->routeParser($quoteFile);

            $this->assertEquals('completed', $quoteFile->processing_status, $this->message($quoteFile));

            $expectedRowsCount = data_get($mapping->firstWhere('filename', '===', $quoteFile->original_file_name), 'count');
            $this->assertEquals($quoteFile->rowsData()->count(), $expectedRowsCount);
        });
    }

    public function message(QuoteFile $quoteFile)
    {
        if ($quoteFile->exception) {
            return "Parsing is failed without Exception on \nFile: {$quoteFile->original_file_path}";
        }

        return "Parsing is failed with Exception: {$quoteFile->exception} on \nFile: {$quoteFile->original_file_path}";
    }

    protected function fakeQuoteFiles()
    {
        $quoteFiles = collect($this->filesList())->map(function ($file) {
            return $this->user->quoteFiles()->make([
                'user_id' => $this->user->id,
                'original_file_path' => $file->getRealPath(),
                'quote_file_format_id' => $this->determineFileFormat($file),
                'file_type' => __('quote_file.types.price'),
                'pages' => $this->parser->countPages($file->getRealPath(), false),
                'imported_page' => 1,
                'original_file_name' => $file->getFilename()
            ]);
        });

        $this->quote->quoteFiles()->saveMany($quoteFiles);

        $quoteFiles->each(function ($quoteFile) {
            $this->preHanlde($quoteFile);
        });

        return $quoteFiles;
    }

    protected function fakeUser()
    {
        return User::create(
            [
                'email' => Str::uuid(),
                'password' => 'password',
                'country_id' => Country::first()->id,
                'timezone_id' => Timezone::first()->id
            ]
        );
    }

    protected function fakeQuote(User $user)
    {
        return $user->quotes()->create(
            [
                'company_id' => Company::first()->id,
                'vendor_id' => Vendor::first()->id,
                'quote_template_id' => QuoteTemplate::first()->id
            ]
        );
    }

    protected function countryName()
    {
        return Str::before(class_basename($this), 'PricesParsingTest');
    }

    protected function filesList()
    {
        $filesPath = "tests/Unit/Parser/data/prices/{$this->countryName()}";

        return File::files($filesPath);
    }

    protected function determineFileFormat(SplFileInfo $file)
    {
        $extensions = collect($file->getExtension());

        if ($extensions->first() === 'txt') {
            $extensions->push('csv');
        }

        return QuoteFileFormat::whereIn('extension', $extensions->toArray())->firstOrFail()->id;
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
}

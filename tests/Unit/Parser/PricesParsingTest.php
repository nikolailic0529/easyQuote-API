<?php namespace Tests\Unit\Parser;

use App\Contracts\Repositories\QuoteFile\QuoteFileRepositoryInterface;
use App\Contracts\Services\ParserServiceInterface;
use App\Contracts\Services\PdfParserInterface;
use App\Contracts\Services\WordParserInterface;
use App\Imports\ImportExcel;
use App\Models \ {
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
use File;

abstract class PricesParsingTest extends TestCase
{
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

    public function setDown(): void
    {
        parent::setDown();

        $this->quoteFiles->each->forceDelete();
        $this->quote->forceDelete();
        $this->user->forceDelete();
    }

    /**
     * A basic unit test example.
     *
     * @return void
     */
    public function testPricesProcessing()
    {
        $this->quoteFiles->each(function ($quoteFile) {
            $this->parser->routeParser($quoteFile);
            $this->assertEquals('completed', $quoteFile->processing_status, $this->message($quoteFile));
        });
    }

    public function message(QuoteFile $quoteFile)
    {
        $exception = $quoteFile->exception;
        if($exception) {
            return "Parsing is failed without Exception on \nFile: {$quoteFile->original_file_path}";
        }

        return "Parsing is failed with Exception: {$exception} on \nFile: {$quoteFile->original_file_path}";

    }

    /**
     * Relative path to samples
     *
     * @return string
     */
    protected function pricesPath()
    {
        $country = Str::before(class_basename($this), 'PricesParsingTest');

        return "tests/Unit/Parser/samples/prices/{$country}";
    }

    private function fakeQuoteFiles()
    {
        $quoteFiles = collect($this->pricesList())->map(function ($file) {
            $original_file_path = $file->getRealPath();
            $quote_file_format_id = $this->determineFileFormat($file);
            return $this->user->quoteFiles()->make(compact('original_file_path', 'quote_file_format_id'));
        });

        $this->quote->quoteFiles()->saveMany($quoteFiles);

        $quoteFiles->each(function ($quoteFile) {
            $this->preHanlde($quoteFile);
        });

        return $quoteFiles;
    }

    private function fakeUser()
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

    private function fakeQuote(User $user)
    {
        return $user->quotes()->create(
            [
                'company_id' => Company::first()->id,
                'vendor_id' => Vendor::first()->id,
                'quote_template_id' => QuoteTemplate::first()->id
            ]
        );
    }

    private function pricesList()
    {
        return File::files($this->pricesPath());
    }

    private function determineFileFormat(SplFileInfo $file) {
        $extensions = collect($file->getExtension());

        if($extensions->first() === 'txt') {
            $extensions->push('csv');
        }

        return QuoteFileFormat::whereIn('extension', $extensions->toArray())->firstOrFail()->id;
    }

    private function preHanlde(QuoteFile $quoteFile)
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

        if($quoteFile->isCsv()) {
            $quoteFile->fullPath = true;
        }
    }
}

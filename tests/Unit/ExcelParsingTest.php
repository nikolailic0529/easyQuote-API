<?php

namespace Tests\Unit;

use App\Imports\ImportExcel;
use App\Models \ {
    User,
    Vendor,
    Company,
    Data\Timezone,
    Data\Country,
    QuoteFile\QuoteFile,
    QuoteTemplate\QuoteTemplate
};
use App\Models\QuoteFile\QuoteFileFormat;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use File, Storage;

class ExcelParsingTest extends TestCase
{
    protected $excelsPath = 'tests/Unit/samples/parser/excel';

    protected $user;

    protected $quote;

    protected $quoteFile;

    public function setUp(): void
    {
        parent::setUp();

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
    public function testExcelsListProcessing()
    {
        $this->quoteFiles->each(function ($quoteFile) {
            (new ImportExcel($quoteFile))->import($quoteFile->original_file_path);
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

    private function fakeQuoteFiles()
    {
        $quote_file_format_id = QuoteFileFormat::whereName('Excel')->first()->id;

        $quoteFiles = collect($this->excelsList())->map(function ($excel) use ($quote_file_format_id) {
            $original_file_path = $excel->getRealPath();
            return $this->user->quoteFiles()->make(compact('original_file_path', 'quote_file_format_id'));
        });

        return $this->quote->quoteFiles()->saveMany($quoteFiles);
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

    private function excelsList()
    {
        return File::files($this->excelsPath);
    }
}

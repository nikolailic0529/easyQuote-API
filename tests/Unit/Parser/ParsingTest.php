<?php

namespace Tests\Unit\Parser;

use Tests\TestCase;
use Tests\Unit\Traits\{
    WithFakeQuote,
    WithFakeQuoteFile,
    WithFakeUser
};
use App\Models\{
    QuoteFile\QuoteFile
};
use App\Services\QuoteFileService;
use App\Contracts\Services\ManagesDocumentProcessors;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * @property ManagesDocumentProcessors $parser
 */
abstract class ParsingTest extends TestCase
{
    use WithFakeUser, WithFakeQuote, WithFakeQuoteFile, DatabaseTransactions;

    public function message(QuoteFile $quoteFile)
    {
        $exception = filled($quoteFile->exception) ? "with Exception: {$quoteFile->exception}" : "without Exception";

        return "Parsing is failed {$exception} on \nFile: {$quoteFile->original_file_path}";
    }

    protected function fakeQuoteFiles(string $country)
    {
        $quote = $this->createQuote($this->user);

        Storage::persistentFake();

        $quoteFiles = collect($this->filesList($country))->map(function ($file) use ($country) {
            Storage::putFileAs('', $file->getRealPath(), $file->getFilename());

            Storage::assertExists($file->getFilename());

            return tap(new QuoteFile([
                'user_id'              => $this->user->getKey(),
                'original_file_path'   => $file->getFilename(),
                'quote_file_format_id' => $this->determineFileFormat($file),
                'file_type'            => $this->filesType(),
                'pages'                => (new QuoteFileService)->countPages($file->getRealPath()),
                'imported_page'        => $this->getMappingAttribute('page', $file->getFilename()),
                'original_file_name'   => $file->getFilename(),
            ]))->save();
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
            $this->parser->forwardProcessor($quoteFile);
            $this->performFileAssertions($quoteFile);
        });
    }

    protected function filesList(string $country)
    {
        $filesPath = "{$this->filesDirPath()}/{$country}";

        return File::files($filesPath);
    }

    protected function getMappingAttribute(string $attribute, string $filename)
    {
        return data_get($this->mapping()->collapse()->keyBy('filename')->get($filename), $attribute);
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

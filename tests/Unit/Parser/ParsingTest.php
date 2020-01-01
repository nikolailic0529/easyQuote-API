<?php

namespace Tests\Unit\Parser;

use App\Models\{
    QuoteFile\QuoteFile
};
use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Collection;
use \File;
use Tests\Unit\Traits\{
    WithFakeQuote,
    WithFakeQuoteFile,
    WithFakeUser
};

abstract class ParsingTest extends TestCase
{
    use DatabaseTransactions, WithFakeUser, WithFakeQuote, WithFakeQuoteFile;

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
            $this->preHandle($quoteFile);
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

    protected function getMappingAttribute(string $attribute, string $filename)
    {
        return data_get($this->mapping()->collapse()->firstWhere('filename', '===', $filename), $attribute);
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

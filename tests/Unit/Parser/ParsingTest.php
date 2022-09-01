<?php

namespace Tests\Unit\Parser;

use App\Contracts\Services\ManagesDocumentProcessors;
use App\Models\{QuoteFile\QuoteFile};
use App\Services\QuoteFileService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\SplFileInfo;
use Tests\TestCase;

abstract class ParsingTest extends TestCase
{
    protected function createQuoteFilesFromDir(string $dir): Collection
    {
        Storage::persistentFake();

        return collect($this->listFilesInDir($dir))
            ->map(function ($file) use ($dir): QuoteFile {
                Storage::putFileAs('', $file->getRealPath(), $file->getFilename());

                Storage::assertExists($file->getFilename());

                return tap(new QuoteFile([
                    'original_file_path' => $file->getFilename(),
                    'quote_file_format_id' => $this->resolveFormatIdOfFile($file),
                    'file_type' => $this->fileType(),
                    'pages' => $this->app[QuoteFileService::class]->countPages($file->getRealPath()),
                    'imported_page' => $this->resolveAttributeFromAssertMapping('page', $file->getFilename()),
                    'original_file_name' => $file->getFilename(),
                ]))->save();
            });
    }

    protected function resolveFormatIdOfFile(\SplFileInfo $file): string
    {
        $extensions = collect($file->getExtension());

        if ($extensions->containsStrict('txt')) {
            $extensions->push('csv');
        }

        return $this->app['db.connection']
            ->table('quote_file_formats')
            ->whereIn('extension', $extensions)
            ->value('id');
    }

    /**
     * Process files from directory.
     *
     * @param string $dir
     * @return void
     */
    protected function processFilesFromDir(string $dir): void
    {
        $quoteFiles = $this->createQuoteFilesFromDir($dir);

        $quoteFiles->each(function ($quoteFile) use ($dir) {
            $this->app[ManagesDocumentProcessors::class]->forwardProcessor($quoteFile);
            $this->performFileAssertions($quoteFile);
        });
    }

    /**
     * @param string $dir
     * @return SplFileInfo[]
     */
    protected function listFilesInDir(string $dir): array
    {
        $dir = ltrim($dir, DIRECTORY_SEPARATOR);
        $filesPath = rtrim($this->filesDirPath(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$dir;

        return File::files($filesPath);
    }

    protected function resolveAttributeFromAssertMapping(string $attribute, string $filename): mixed
    {
        $fileMapping = $this->assertionMapping()
            ->collapse()
            ->lazy()
            ->where('filename', $filename)
            ->first();

        return data_get($fileMapping, $attribute);
    }

    /**
     * Get Mapping for specified Parsing.
     *
     * @return \Illuminate\Support\Collection
     */
    abstract protected function assertionMapping(): Collection;

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
    abstract protected function fileType(): string;

    /**
     * Perform specified assertions on each parsed file.
     *
     * @param QuoteFile $quoteFile
     * @return void
     */
    abstract protected function performFileAssertions(QuoteFile $quoteFile): void;
}

<?php

namespace App\Console\Commands;

use App\Contracts\Services\PdfParserInterface;
use Exception;
use Illuminate\Console\Command;

class GenerateTestOfPdfPriceListFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eq:generate-test:pdf-price-list {filepath}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate test code of the particular pdf file';

    protected $hidden = true;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     * @throws \Exception
     */
    public function handle(PdfParserInterface $parser)
    {
        $filePath = $this->argument('filepath');

        if (!file_exists($filePath)) {
            throw new Exception("File '$filePath' not found.");
        }

        $this->getOutput()->text('$parser = $this->app[\\'.PdfParserInterface::class.'::class];');

        $fileContent = $parser->getText($filePath);

        $this->getOutput()->text('$fileContent = $parser->getText($filePath);');

        $fileData = $parser->parse($fileContent);

        $this->getOutput()->text('$fileData = $parser->parse($fileContent);');

        $this->getOutput()->newLine();

        foreach ($fileData['pages'] as $key => $page) {

            if (empty($page['rows'])) {
                $this->getOutput()->text('$this->assertEmpty($fileData[\'pages\']['.$key.'][\'rows\']);');
            } else {
                $this->getOutput()->text('$this->assertCount('.count($page['rows']).', $fileData[\'pages\']['.$key.'][\'rows\']);');
                $this->getOutput()->newLine();

                foreach ($page['rows'] as $row) {

                    $this->getOutput()->text('$this->assertContainsEquals('.static::varExport($row, true).', $fileData[\'pages\']['.$key.'][\'rows\']);');
                    $this->getOutput()->newLine();

                }

            }

            $this->getOutput()->newLine();

        }

        return Command::SUCCESS;
    }

    private static function varExport($expression, bool $return = false)
    {
        $export = var_export($expression, TRUE);
        $export = preg_replace("/^([ ]*)(.*)/m", '$1$1$2', $export);
        $array = preg_split("/\r\n|\n|\r/", $export);
        $array = preg_replace(["/\s*array\s\($/", "/\)(,)?$/", "/\s=>\s$/"], [NULL, ']$1', ' => ['], $array);
        $export = join(PHP_EOL, array_filter(["["] + $array));

        if ($return) {
            return $export;
        }

        echo $export;
    }
}

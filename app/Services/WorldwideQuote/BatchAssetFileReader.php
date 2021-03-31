<?php

namespace App\Services\WorldwideQuote;

use App\DTO\Opportunity\CreateOpportunityData;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Factory as ValidatorFactory;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class BatchAssetFileReader
{
    const HEADER_ROW_INDEX = 1;

    protected string $filePath;

    protected string $fileType;

    protected array $headers = [];

    protected string $headerCountSeparator;

    protected bool $fileContainsHeaders = true;

    public function __construct(string $filePath, string $fileType)
    {
        $this->filePath = $filePath;
        $this->fileType = $fileType;
        $this->headerCountSeparator = Str::random(20);
    }

    public function fileContainsHeaders(bool $value = true): self
    {
        $this->fileContainsHeaders = $value;

        return $this;
    }

    public function getRows(): \Iterator
    {
        $reader = ReaderFactory::createFromType($this->fileType);

        libxml_disable_entity_loader(false);

        $reader->open($this->filePath);

        $reader->getSheetIterator()->rewind();

        $sheet = $reader->getSheetIterator()->current();

        $this->processHeaders($sheet);

        return $this->getRowIterator($sheet);
    }

    public function getChunks(int $size): \Iterator
    {
        $rowsIterator = $this->getRows();

        while ($rowsIterator->valid()) {
            $chunk = [];

            while (true) {
                $chunk[$rowsIterator->key()] = $rowsIterator->current();

                if (count($chunk) < $size) {
                    $rowsIterator->next();

                    if (!$rowsIterator->valid()) {
                        break;
                    }
                } else {
                    break;
                }
            }

            yield $chunk;

            $rowsIterator->next();
        }
    }

    public function getHeaderCountSeparator(): string
    {
        return $this->headerCountSeparator;
    }

    public function getHeaders(): array
    {
        if (empty($this->headers)) {
            $reader = ReaderFactory::createFromType($this->fileType);

            libxml_disable_entity_loader(false);

            $reader->open($this->filePath);

            $reader->getSheetIterator()->rewind();

            $sheet = $reader->getSheetIterator()->current();

            $this->processHeaders($sheet);
        }

        return $this->headers;
    }

    protected function getRowIterator(SheetInterface $sheet): \Iterator
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        if ($this->fileContainsHeaders) {
            // skip the header row.
            $rowIterator->next();
        }

        while ($rowIterator->valid()) {
            /** @var Row $row */
            $row = $rowIterator->current();

            yield $this->getValueFromRow($row);

            $rowIterator->next();
        }
    }

    protected function processHeaders(SheetInterface $sheet): void
    {
        $rowIterator = $sheet->getRowIterator();

        $rowIterator->rewind();

        /** @var Row $firstRow */
        $firstRow = $rowIterator->current();

        foreach ($firstRow->toArray() as $value) {
            $this->headers[Str::slug($value, '_')] = $value;
        }
    }

    protected function getValueFromRow(Row $row): array
    {
        $values = $row->toArray();
        ksort($values);

        $values = array_slice($values, 0, count($this->headers));

        while (count($values) < count($this->headers)) {
            $values[] = null;
        }

        $values = array_map(function ($value) {
            if (is_string($value) && trim($value) === "") {
                return null;
            }

            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d');
            }

            return $value;
        }, $values);

        return array_combine(array_keys($this->headers), $values);
    }
}

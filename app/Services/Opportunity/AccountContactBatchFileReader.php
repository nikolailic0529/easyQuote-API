<?php

namespace App\Services\Opportunity;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\ReaderInterface;
use Box\Spout\Reader\SheetInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AccountContactBatchFileReader
{
    const HEADER_ROW_INDEX = 1;

    const ROW_KEY_HEADER = 'primary_account_name';

    protected ReaderInterface $reader;

    protected string $filePath;

    protected string $fileType;

    protected array $headers = [];

    protected string $headerCountSeparator;

    public function __construct(string $filePath, string $fileType)
    {
        $this->filePath = $filePath;
        $this->fileType = $fileType;
        $this->headerCountSeparator = Str::random(20);

        $libxmlEntityLoaderPreviousValue = null;

        if (\PHP_VERSION_ID < 80000) {
            $libxmlEntityLoaderPreviousValue = libxml_disable_entity_loader(false);
        }

        $this->reader = ReaderFactory::createFromType($this->fileType);
        $this->reader->open($filePath);

        if (!is_null($libxmlEntityLoaderPreviousValue)) {
            libxml_disable_entity_loader($libxmlEntityLoaderPreviousValue);
        }
    }

    public function getRows(): \Iterator
    {
        $this->reader->getSheetIterator()->rewind();

        $sheet = $this->reader->getSheetIterator()->current();

        $this->processHeaders($sheet);

        return $this->getRowIterator($sheet);
    }

    public function getHeaderCountSeparator(): string
    {
        return $this->headerCountSeparator;
    }

    protected function getRowIterator(SheetInterface $sheet): \Iterator
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        foreach (range(0, static::HEADER_ROW_INDEX) as $i) {
            $rowIterator->next();
        }

        while ($rowIterator->valid()) {
            /** @var Row $row */
            $row = $rowIterator->current();

            $valueFromRow = $this->getValueFromRow($row);

            if (isset($valueFromRow[self::ROW_KEY_HEADER])) {
                yield md5($valueFromRow[self::ROW_KEY_HEADER]) => $valueFromRow;
            }

            $rowIterator->next();
        }
    }

    protected function processHeaders(SheetInterface $sheet): void
    {
        $rowIterator = $sheet->getRowIterator();

        $rowIterator->rewind();

        $offset = 0;

        while ($offset < static::HEADER_ROW_INDEX) {
            $rowIterator->next();
            $offset++;
        }

        $headerRowArray = value(function () use (&$offset, $rowIterator): array {
            while ($rowIterator->valid()) {
                /** @var Row $firstRow */
                $firstRow = $rowIterator->current();

                $firstRowArray = $this->rowToArray($firstRow);

                $filteredRowArray = array_filter($firstRowArray, fn(string $value) => !empty($value));

                if (!empty($filteredRowArray)) {
                    return $firstRowArray;
                }

                $rowIterator->next();

                $offset++;
            }

            return [];
        });

        $this->headers = array_map(function (string $header) {
            return Str::slug($header, '_');
        }, $headerRowArray);

        // Collating suppliers data.
        // from: country country country supplier supplier suppler contact_name contact_name contact_name email_address email_address email_address

        $duplicatedHeadersCount = [];

        $this->headers = array_map(function (string $header) use (&$duplicatedHeadersCount) {
            $key = $header;

            if (!isset($duplicatedHeadersCount[$key])) {
                $duplicatedHeadersCount[$key] = 0;
            }

            if ($duplicatedHeadersCount[$header] > 0) {
                $header = $header.$this->getHeaderCountSeparator().$duplicatedHeadersCount[$header];
            }

            $duplicatedHeadersCount[$key]++;

            return $header;
        }, $this->headers);
    }

    private function rowToArray(Row $row): array
    {
        return array_map(function (Cell $cell) {
            $value = $cell->getValue();

            if ($cell->isDate()) {
                return Carbon::createFromTimestamp($value->getTimestamp())->toString();
            }

            if (is_string($value)) {
                return trim($value);
            }

            return $value;
        }, $row->getCells());
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

            if (is_string($value)) {
                return trim($value);
            }

            return $value;
        }, $values);

        return array_combine($this->headers, $values);
    }
}

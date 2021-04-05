<?php

namespace App\Services\Opportunity;

use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\SheetInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OpportunityBatchFileReader
{
    protected int $headerRowIndex = 0;

    protected string $filePath;

    protected string $fileType;

    protected array $headers = [];

    protected string $headerCountSeparator;

    public function __construct(string $filePath, string $fileType)
    {
        $this->filePath = $filePath;
        $this->fileType = $fileType;
        $this->headerCountSeparator = Str::random(20);
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

    public function getHeaderCountSeparator(): string
    {
        return $this->headerCountSeparator;
    }

    protected function getRowIterator(SheetInterface $sheet): \Iterator
    {
        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        $startRowIndex = 0;

        while ($rowIterator->valid() && $this->headerRowIndex >= $startRowIndex) {
            $rowIterator->next();
            $startRowIndex++;
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

                $this->headerRowIndex++;
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

        $values = array_combine($this->headers, $values);

        // Collating suppliers data.
        // Pick the first supplier data.
        $suppliersData = [
            ['country' => $values['distributor_country'] ?? null, 'supplier' => $values['distributor'] ?? null, 'contact_name' => $values['contact_name'] ?? null, 'email_address' => $values['email_address'] ?? null]
        ];

        $otherSuppliersData = [];

        $supplierKeyNeedles = [
            'supplier' => 'distributor'.$this->getHeaderCountSeparator(),
            'country' => 'distributor_country'.$this->getHeaderCountSeparator(),
            'contact_name' => 'contact_name'.$this->getHeaderCountSeparator(),
            'email_address' => 'email_address'.$this->getHeaderCountSeparator(),
        ];

        foreach ($values as $key => $value) {
            if (Str::startsWith($key, $supplierKeyNeedles)) {
                $supplerKey = (int)Str::after($key, $this->getHeaderCountSeparator());
                $supplerHeader = array_flip($supplierKeyNeedles)[Str::before($key, $this->getHeaderCountSeparator()).$this->getHeaderCountSeparator()];

                $otherSuppliersData[$supplerKey][$supplerHeader] = $value;
            }
        }

        $suppliersData = array_values(array_merge($suppliersData, $otherSuppliersData));

        $values['suppliers'] = $suppliersData;

        return $values;
    }
}

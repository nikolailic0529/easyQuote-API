<?php

namespace App\Domain\DocumentProcessing\EasyQuote\Parsers;

use App\Domain\DocumentProcessing\EasyQuote\Parsers\Exceptions\PaymentScheduleParserException;
use App\Domain\DocumentProcessing\EasyQuote\Parsers\Models\PaymentScheduleCollection;
use App\Domain\DocumentProcessing\EasyQuote\Parsers\Models\PaymentScheduleData;
use Box\Spout\Common\Entity\Cell;
use Box\Spout\Common\Entity\Row;
use Box\Spout\Common\Exception\IOException;
use Box\Spout\Common\Exception\UnsupportedTypeException;
use Box\Spout\Common\Type;
use Box\Spout\Reader\Common\Creator\ReaderFactory;
use Box\Spout\Reader\XLSX\RowIterator;
use Box\Spout\Reader\XLSX\Sheet;
use Devengine\AnyDateParser\DateParser;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

class ExcelPaymentScheduleParser
{
    const DATE_REGEXP = '#^((?:[0-2][0-9]|3[0-1])[./][0-9][0-9][./]\d{2,4})$#';
    const VALUE_REGEXP = '#^((\p{Sc})? ?([\d]+ ?,?)?[,.]?\d+[,.]?\d+)$#';

    /**
     * @throws UnsupportedTypeException
     * @throws IOException
     * @throws PaymentScheduleParserException
     */
    public function parse(\SplFileInfo $file, int $sheetNumber): PaymentScheduleCollection
    {
        $reader = ReaderFactory::createFromType(Type::XLSX);
        $reader->setShouldPreserveEmptyRows(true);
        $reader->setShouldFormatDates(true);

        $reader->open($file->getRealPath());

        $sheets = LazyCollection::make(static function () use ($reader): \Generator {
            yield from $reader->getSheetIterator();
        });

        /** @var Sheet|null $sheet */
        $sheet = $sheets->get($sheetNumber);

        if (null === $sheet) {
            throw new \RuntimeException("Could not obtain sheet #$sheetNumber");
        }

        $rowIterator = $sheet->getRowIterator();
        $rowIterator->rewind();

        $dates = $this->searchRowWithDates($rowIterator);

        if ($dates->count() < 2) {
            throw PaymentScheduleParserException::couldNotMatchPaymentDates();
        }

        $values = $this->searchRowWithPayments($rowIterator, $dates->first());

        if (null === $values) {
            throw PaymentScheduleParserException::couldNotMatchPaymentValues();
        }

        [$startDates, $endDates] = $dates->all();

        return $this->mapPaymentDatesWithValues($startDates, $endDates, $values);
    }

    private function mapPaymentDatesWithValues(Row $startDates, Row $endDates, Row $payments): PaymentScheduleCollection
    {
        return static::matchValuesFromRow($payments)
            ->map(static function (string $value, int $i) use ($startDates, $endDates): PaymentScheduleData {
                [$startDateCell, $endDateCell] = [$startDates->getCellAtIndex($i), $endDates->getCellAtIndex($i)];

                return new PaymentScheduleData(
                    from: $startDateCell,
                    to: $endDateCell,
                    price: $value,
                );
            })
            ->values()
            ->pipeInto(PaymentScheduleCollection::class);
    }

    private function searchRowWithPayments(RowIterator $rowIterator, Row $rowWithDates): ?Row
    {
        $candidate = null;

        while ($rowIterator->valid()) {
            $row = $rowIterator->current();

            if ($candidate && $row->getNumCells() === 0) {
                $rowIterator->next();

                return $candidate;
            }

            if (static::rowContainsPricesOnTheSamePositionsAs($row, $rowWithDates)) {
                $candidate = $row;
            }

            $rowIterator->next();
        }

        return null;
    }

    private function searchRowWithDates(RowIterator $rowIterator): Collection
    {
        $matchedRows = Collection::empty();

        while ($rowIterator->valid()) {
            $row = $rowIterator->current();

            // When the candidate row is set, check the next row under it
            // Break the loop when the next row contains the dates,
            // otherwise unset the candidate and continue.
            if ($matchedRows->isNotEmpty() && static::rowContainsDatesOnTheSamePositionsAs($row, $matchedRows[0])) {
                $matchedRows->push($row);

                $rowIterator->next();

                break;
            }

            if (static::rowContainsDates($row)) {
                $matchedRows = Collection::make([$row]);
            }

            $rowIterator->next();
        }

        return $matchedRows;
    }

    private static function rowContainsDatesOnTheSamePositionsAs(Row $row, Row $another): bool
    {
        return static::matchDatesFromRow($row)->keys()->all() === static::matchDatesFromRow($another)->keys()->all();
    }

    private static function rowContainsPricesOnTheSamePositionsAs(Row $row, Row $another): bool
    {
        return static::matchValuesFromRow($row)->keys()->all() === static::matchDatesFromRow($another)->keys()->all();
    }

    private static function rowContainsDates(Row $row): bool
    {
        return static::matchDatesFromRow($row)->isNotEmpty();
    }

    private static function matchDatesFromRow(Row $row): Collection
    {
        return collect($row->getCells())
            ->lazy()
            ->map(static function (Cell $cell): mixed {
                return $cell->getValue();
            })
            ->filter(static function (mixed $value): bool {
                if ($value instanceof \DateInterval) {
                    return false;
                }

                if ($value instanceof \DateTimeInterface) {
                    return true;
                }

                return is_string($value) && filled($value) && (new DateParser($value))->parseSilent() !== null;
            })
            ->pipe(static function (LazyCollection $collection): Collection {
                return collect($collection->all());
            });
    }

    private static function matchValuesFromRow(Row $row): Collection
    {
        return collect($row->getCells())
            ->lazy()
            ->filter(static function (Cell $cell): bool {
                return is_scalar($cell->getValue()) && filled($cell);
            })
            ->filter(static function (string $value): bool {
                return is_numeric($value) || preg_match(self::VALUE_REGEXP, (string) $value);
            })
            ->pipe(static function (LazyCollection $collection): Collection {
                return collect($collection->all());
            });
    }
}

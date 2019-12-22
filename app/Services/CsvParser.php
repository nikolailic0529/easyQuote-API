<?php

namespace App\Services;

use App\Contracts\Services\CsvParserInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\LazyCollection;

class CsvParser implements CsvParserInterface
{
    const GFDE_01 = 'The file doesn\'t exist.';

    const GFNE_01 = 'The file has non-acceptable extension';

    protected static $defaultDelimiter = 'tab';

    protected static $delimiters = ['tab' => "\t", 'semicolon' => ";", 'comma' => ",", 'colon' => ":"];

    protected static $extenstions = ['csv', 'txt'];

    protected static $readLines = 5;

    protected static $regexp;

    public function guessDelimiter(string $filepath): string
    {
        $this->checkFileExisting($filepath);
        $this->checkFileExtension($filepath);

        $reader = $this->makeReader($filepath);

        $counts = $reader->mapWithKeys(function ($line, $key) {
            $result = collect(static::$delimiters)->map(function ($delimiter, $key) use ($line) {
                return substr_count($line, $delimiter);
            });
            return [$key => $result];
        })->all();

        $max2 = $this->calculateScores($counts);

        $first = $max2->shift();
        $second = $max2->shift();

        $max = $second['variance'] > $first['variance'] && $first['value'] < 0.5 && $second['value'] > $first['value']
            ? $second
            : $first;

        $delimiter = $max['key'];

        return $delimiter;
    }

    protected function makeReader(string $filepath): LazyCollection
    {
        return LazyCollection::make(function () use ($filepath) {
            $file = fopen($filepath, 'r');
            $readLines = 0;

            while (($line = fgets($file)) !== false && $readLines < static::$readLines) {
                $readLines++;
                yield $line;
            }
        });
    }

    protected function calculateScores(iterable &$results): Collection
    {
        $results = collect()->wrap($results);

        // Average delimiter calculation.
        $average = collect(array_keys(static::$delimiters))
            ->map(function ($key) use ($results) {
                return ['key' => $key, 'value' => (float) $results->avg($key)];
            });


        /**
         * Variance delimiter on each line.
         * We are using variance because it helps more accurately calculate determine the delimiter.
         */
        $variance = collect(array_keys(static::$delimiters))
            ->map(function ($key) use ($results) {
                $avg = $results->avg($key);
                $value = collect($results->pluck($key))
                    ->reduce(function ($carry, $value) use ($avg) {
                        return $carry + (($value - $avg) ** 2);
                    }, 0);

                $count = $results->count();

                $value /= max($count - 1, 1);

                $value = (float) $value;

                return compact('key', 'value');
            });


        // Normalizing values gives value from 0 to 1.
        $this->normalizeValues($average);
        $this->normalizeValues($variance);

        // Pull two max values from average.
        $max2 = $average->sortByDesc('value')->slice(0, 2)
            ->transform(function ($delimiter) use ($variance) {
                $variance = $variance->firstWhere('key', $delimiter['key']);
                data_set($delimiter, 'variance', $variance['value']);
                return $delimiter;
            })
            ->sortBy('variance')
            ->values();

        return $max2;
    }

    protected function normalizeValues(iterable &$values): void
    {
        $values = collect()->wrap($values);

        $min = $values->min('value');
        $max = $values->max('value');
        $count = $values->count();

        $values->transform(function ($delimiter) use ($min, $max, $count) {
            $value = $max === $min
                ? 1 / $count
                : ($delimiter['value'] - $min) / ($max - $min);

            data_set($delimiter, 'value', $value);
            return $delimiter;
        });
    }

    protected function checkFileExisting(string $filepath): void
    {
        throw_if(File::missing($filepath), new \Exception(self::GFDE_01));
    }

    protected function checkFileExtension(string $filepath): void
    {
        $acceptableExtension = isset(array_flip(static::$extenstions)[File::extension($filepath)]);

        throw_unless($acceptableExtension, new \Exception(self::GFNE_01));
    }
}

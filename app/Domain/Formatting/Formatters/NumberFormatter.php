<?php

namespace App\Domain\Formatting\Formatters;

use Illuminate\Contracts\Config\Repository;

class NumberFormatter implements Formatter
{
    public function __construct(protected Repository $config)
    {
    }

    public function __invoke(mixed $value, mixed ...$parameters): string
    {
        $prepend = (string) ($parameters['prepend'] ?? '');

        $result = number_format(num: (float) $value, decimals: 2);

        if ('' !== $prepend) {
            $result = $prepend.' '.$result;
        }

        return $result;
    }
}

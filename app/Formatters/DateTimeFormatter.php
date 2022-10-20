<?php

namespace App\Formatters;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository;

class DateTimeFormatter implements Formatter
{
    public function __construct(protected Repository $config)
    {
    }

    public function __invoke(mixed $value, mixed ...$parameters): string
    {
        if (is_null($value)) {
            return '';
        }

        $format = $this->config->get('formatters.date_time.default');

        $fromFormat = $parameters['fromFormat'] ?? null;

        $dateInstance = isset($fromFormat) ? Carbon::createFromFormat($fromFormat, $value) : Carbon::parse($value);

        return $dateInstance->format($format);
    }
}
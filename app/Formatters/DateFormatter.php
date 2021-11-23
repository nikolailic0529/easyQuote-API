<?php

namespace App\Formatters;

use Carbon\Carbon;
use Illuminate\Contracts\Config\Repository;

class DateFormatter implements Formatter
{
    public function __construct(protected Repository $config)
    {
    }

    public function __invoke(mixed $value, mixed ...$parameters): string
    {
        if (is_null($value)) {
            return '';
        }

        return Carbon::parse($value)->format($this->config->get('formatters.date_format'));
    }
}
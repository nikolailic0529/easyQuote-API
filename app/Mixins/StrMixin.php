<?php

namespace App\Mixins;

class StrMixin
{
    public function header()
    {
        return function ($value, $default = null, $perform = true) {
            if (!$perform) {
                return $value;
            }

            if (!isset($value)) {
                return $default;
            }

            return self::title(str_replace('_', ' ', $value));
        };
    }

    public function columnName()
    {
        return function ($value) {
            return self::snake(preg_replace('/\W/', '', $value));
        };
    }

    public function price()
    {
        return function ($value, bool $format = false, bool $detectDelimiter = false) {
            if ($detectDelimiter) {
                if (preg_match('/\d+ \d+(,)\d{1,2}/', $value)) {
                    $value = str_replace(',', '.', $value);
                }

                if (!preg_match('/[,\.]/', $value)) {
                    $value = str_replace(',', '', $value);
                }

                if (preg_match('/,/', $value) && !preg_match('/\./', $value)) {
                    $value = str_replace(',', '.', $value);
                }
            }

            $value = (float) preg_replace('/[^\d\.]/', '', $value);

            if ($format) {
                return number_format($value, 2);
            }

            return $value;
        };
    }

    public function decimal()
    {
        return function ($value, int $precision = 2) {
            if (!is_string($value) && !is_numeric($value)) {
                return $value;
            }

            return number_format(round((float) $value, $precision), $precision, '.', '');
        };
    }

    public function short()
    {
        return function ($value) {
            preg_match_all('/\b[a-zA-Z]/', $value, $matches);
            return implode('', $matches[0]);
        };
    }

    public function name()
    {
        return function ($value) {
            return self::snake(Str::snake(preg_replace('/[^\w\h]/', ' ', $value)));
        };
    }

    public function prepend()
    {
        return function (string $value, ?string $prependable, bool $noBreak = false) {
            $space = $noBreak ? "\xC2\xA0" : ' ';

            return filled($prependable) ? "{$prependable}{$space}{$value}" : $value;
        };
    }

    public function formatAttributeKey()
    {
        return function (string $value) {
            $value = static::before($value, '.');

            if (!ctype_lower($value)) {
                $value = preg_replace('/\s+/u', '', ucwords($value));
                $value = static::lower(preg_replace('/(.)(?=[A-Z])/u', '$1 ', $value));
            }

            return ucwords(str_replace(['-', '_'], ' ', $value));
        };
    }

    public function spaced()
    {
        return function (string $value) {
            if (!ctype_lower($value) && !ctype_upper($value)) {
                $value = preg_replace('/(.)(?=[A-Z])/u', '$1 ', $value);
            }

            return $value;
        };
    }

    public function filterLetters()
    {
        return function (string $value) {
            return preg_replace('/[^[:alpha:]]/', '', $value);
        };
    }
}

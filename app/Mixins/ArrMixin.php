<?php

namespace App\Mixins;

class ArrMixin
{
    public function lower()
    {
        return function (array $array) {
            return array_map(function ($item) {
                if (!is_string($item)) {
                    return $item;
                }

                return mb_strtolower($item);
            }, $array);
        };
    }

    public function quote()
    {
        return function ($value) {
            return implode(',', array_map('json_encode', $value));
        };
    }

    public function cols()
    {
        return function (array $value, string $append = '') {
            return implode(', ', array_map(function ($item) use ($append) {
                return "`{$item}`{$append}";
            }, $value));
        };
    }

    public function udiff()
    {
        return function (array $array, array $array2, bool $both = true) {
            return array_udiff($array, $array2, function ($first, $second) use ($both) {
                if ($both) {
                    return $first !== $second ? -1 : 0;
                }

                return $first <=> $second;
            });
        };
    }

    public function udiffAssoc()
    {
        return function (array $array, array $array2) {
            return array_udiff_assoc($array, $array2, function ($first, $second) {
                if (is_null($first) || is_null($second)) {
                    return $first === $second ? 0 : 1;
                }

                return $first <=> $second;
            });
        };
    }

    public function isDifferentAssoc()
    {
        return function (array $array, array $array2) {
            return filled(static::udiffAssoc($array, $array2));
        };
    }
}

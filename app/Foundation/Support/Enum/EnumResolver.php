<?php

declare(strict_types=1);

namespace App\Foundation\Support\Enum;

class EnumResolver
{
    /**
     * @template T of EnumResolver
     *
     * @param class-string<T> $fqn
     *
     * @return T|null
     */
    public static function fromKey(string $fqn, string $keyName): ?\UnitEnum
    {
        $matchingItems = array_values(array_filter($fqn::cases(), static fn (\UnitEnum $case) => $case->name === $keyName));

        if (array_key_exists(0, $matchingItems) === false || count($matchingItems) !== 1) {
            return null;
        }

        return $matchingItems[0];
    }
}

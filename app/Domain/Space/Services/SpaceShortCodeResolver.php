<?php

namespace App\Domain\Space\Services;

final class SpaceShortCodeResolver
{
    public function __invoke(string $spaceName): string
    {
        $value = $spaceName;

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        preg_match_all('/[A-Z]+/', $value, $matches);

        return implode('', $matches[0]);
    }
}

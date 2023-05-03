<?php

namespace App\Domain\Authorization\Repositories\Models;

final class Privilege
{
    public function __construct(
        public readonly string $level,
        public readonly array $permissions,
    ) {
    }
}

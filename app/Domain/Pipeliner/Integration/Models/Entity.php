<?php

namespace App\Domain\Pipeliner\Integration\Models;

use Illuminate\Support\Carbon;

class Entity
{
    public static function parseDateTime(?string $dateTimeStr): ?\DateTimeImmutable
    {
        return isset($dateTimeStr)
            ? Carbon::parse($dateTimeStr)->toDateTimeImmutable()
            : null;
    }
}

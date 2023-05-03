<?php

namespace App\Domain\Worldwide\Enum;

use App\Foundation\Support\Enum\Enum;

final class QuoteStatus extends Enum
{
    const DEAD = 0;
    const ALIVE = 1;
}

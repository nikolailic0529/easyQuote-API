<?php

namespace App\Enum;

use ArchTech\Enums\Options;

enum DistributionAlgorithmEnum: string
{
    use Options;

    case Evenly = 'Evenly';
    case Equalize = 'Equalize';
}

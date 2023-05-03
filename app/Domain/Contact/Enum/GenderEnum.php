<?php

namespace App\Domain\Contact\Enum;

enum GenderEnum: string
{
    case Unknown = 'Unknown';
    case Male = 'Male';
    case Female = 'Female';
    case NonBinary = 'NonBinary';
}

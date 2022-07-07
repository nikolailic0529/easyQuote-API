<?php

namespace App\Enum;

enum GenderEnum: string
{
    case Unknown = 'Unknown';
    case Male = 'Male';
    case Female = 'Female';
    case NonBinary = 'NonBinary';
}
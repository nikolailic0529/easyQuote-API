<?php

namespace App\DTO\QueryFilter\Enum;

enum FilterTypeEnum: string
{
    case Multiselect = 'multiselect';
    case Textbox = 'textbox';
    case Checkbox = 'checkbox';
}

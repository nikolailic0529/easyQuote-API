<?php

namespace App\Foundation\Database\Eloquent\QueryFilter\DataTransferObjects\Enum;

enum FilterTypeEnum: string
{
    case Multiselect = 'multiselect';
    case Textbox = 'textbox';
    case Checkbox = 'checkbox';
}

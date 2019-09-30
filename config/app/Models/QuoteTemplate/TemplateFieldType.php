<?php namespace App\Models\QuoteTemplate;

use App\Models\UuidModel;
use App\Traits\BelongsToTemplateFields;

class TemplateFieldType extends UuidModel
{
    use BelongsToTemplateFields;
}
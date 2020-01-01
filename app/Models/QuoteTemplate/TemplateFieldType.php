<?php

namespace App\Models\QuoteTemplate;

use App\Models\BaseModel;
use App\Traits\BelongsToTemplateFields;

class TemplateFieldType extends BaseModel
{
    use BelongsToTemplateFields;
}

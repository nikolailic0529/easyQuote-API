<?php

namespace App\Models\QuoteTemplate;

use App\Traits\{
    Uuid,
    BelongsToTemplateFields,
};
use Illuminate\Database\Eloquent\Model;

class TemplateFieldType extends Model
{
    use Uuid, BelongsToTemplateFields;
}

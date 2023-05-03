<?php

namespace App\Domain\Template\Models;

use App\Domain\Template\Concerns\{BelongsToTemplateFields};
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

class TemplateFieldType extends Model
{
    use Uuid;
    use BelongsToTemplateFields;
}

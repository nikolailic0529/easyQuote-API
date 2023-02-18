<?php

namespace App\Domain\Pipeliner\Models;

use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $entity_name
 * @property string|null $name
 * @property string|null $api_name
 * @property string|null $eq_reference
 */
class PipelinerCustomField extends Model
{
    use Uuid;
    use HasFactory;

    protected $guarded = [];
}

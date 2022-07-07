<?php

namespace App\Models;

use App\Enum\RecurrenceTypeEnum;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $value
 */
class RecurrenceType extends Model
{
    use Uuid;

    protected $guarded = [];

    public function toEnum(): RecurrenceTypeEnum
    {
        return RecurrenceTypeEnum::from($this->value);
    }
}

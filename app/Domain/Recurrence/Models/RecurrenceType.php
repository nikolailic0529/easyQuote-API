<?php

namespace App\Domain\Recurrence\Models;

use App\Domain\Recurrence\Enum\RecurrenceTypeEnum;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
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

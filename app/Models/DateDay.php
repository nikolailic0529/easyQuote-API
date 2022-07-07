<?php

namespace App\Models;

use App\Enum\DateDayEnum;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $value
 */
class DateDay extends Model
{
    use Uuid;

    protected $guarded = [];

    public function toEnum(): DateDayEnum
    {
        return DateDayEnum::from($this->value);
    }

    public function toDayNumber(): int
    {
        return $this->toEnum()->toDayNumber();
    }
}

<?php

namespace App\Domain\Date\Models;

use App\Domain\Date\Enum\DateDayEnum;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
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

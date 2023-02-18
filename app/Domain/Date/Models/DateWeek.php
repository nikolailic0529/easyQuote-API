<?php

namespace App\Domain\Date\Models;

use App\Domain\Date\Enum\DateWeekEnum;
use App\Domain\Shared\Eloquent\Concerns\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $value
 */
class DateWeek extends Model
{
    use Uuid;

    protected $guarded = [];

    public function toEnum(): DateWeekEnum
    {
        return DateWeekEnum::from($this->value);
    }
}

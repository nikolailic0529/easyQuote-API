<?php

namespace App\Models;

use App\Enum\DateWeekEnum;
use App\Traits\Uuid;
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

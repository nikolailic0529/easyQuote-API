<?php

namespace App\Models;

use App\Enum\DateMonthEnum;
use App\Traits\Uuid;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string|null $value
 */
class DateMonth extends Model
{
    use Uuid;

    protected $guarded = [];

    public function toEnum(): DateMonthEnum
    {
        return DateMonthEnum::from($this->value);
    }

    public function toMonthNumber(): int
    {
        return $this->toEnum()->toMonthNumber();
    }
}

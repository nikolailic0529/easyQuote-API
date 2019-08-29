<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Contracts\Repositories\TimezoneRepositoryInterface;

class TimezonesController extends Controller
{
    protected $timezone;

    public function __construct(TimezoneRepositoryInterface $timezone)
    {
        $this->timezone = $timezone;
    }

    public function __invoke()
    {
        $timezones = $this->timezone->all();
        return response()->json($timezones);
    }
}

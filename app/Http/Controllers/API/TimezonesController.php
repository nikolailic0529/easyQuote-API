<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Timezone;

class TimezonesController extends Controller
{
    protected $timezone;

    public function __construct(Timezone $timezone)
    {
        $this->timezone = $timezone;
    }

    public function __invoke()
    {
        $timezones = $this->timezone->ordered()->get();
        return response()->json($timezones);
    }
}

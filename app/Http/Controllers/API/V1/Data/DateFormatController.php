<?php

namespace App\Http\Controllers\API\V1\Data;

use App\Enum\DateFormatEnum;
use App\Http\Controllers\Controller;
use App\Http\Resources\V1\DateFormat\DateFormatResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DateFormatController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        return DateFormatResource::collection(DateFormatEnum::cases());
    }
}

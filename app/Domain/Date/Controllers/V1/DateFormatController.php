<?php

namespace App\Domain\Date\Controllers\V1;

use App\Domain\Date\Enum\DateFormatEnum;
use App\Domain\Formatting\Resources\V1\DateFormatResource;
use App\Foundation\Http\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DateFormatController extends Controller
{
    public function __invoke(): AnonymousResourceCollection
    {
        return DateFormatResource::collection(DateFormatEnum::cases());
    }
}

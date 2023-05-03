<?php

namespace App\Domain\Rescue\Requests;

use App\Domain\Margin\Enum\MarginMethodEnum;
use App\Domain\Margin\Enum\MarginQuoteTypeEnum;
use App\Domain\Margin\Enum\MarginTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

final class ShowThirdStepDataRequest extends FormRequest
{
    public function rules(): array
    {
        return [];
    }

    public function getData(): array
    {
        return [
            'quote_types' => collect(MarginQuoteTypeEnum::cases())->map->value,
            'margin_types' => collect(MarginTypeEnum::cases())->map->value,
            'margin_methods' => collect(MarginMethodEnum::cases())->reject(MarginMethodEnum::Standard)->values()->map->value,
        ];
    }
}
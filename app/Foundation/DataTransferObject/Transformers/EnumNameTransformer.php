<?php

namespace App\Foundation\DataTransferObject\Transformers;

use Spatie\LaravelData\Support\DataProperty;
use Spatie\LaravelData\Transformers\Transformer;

class EnumNameTransformer implements Transformer
{
    public function transform(DataProperty $property, mixed $value): mixed
    {
        return $value->name;
    }
}

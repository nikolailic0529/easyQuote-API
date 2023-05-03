<?php

namespace App\Domain\Note\DataTransferObjects;

use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class CreateNoteData extends Data
{
    public function __construct(
        #[Required, StringType, Max(20_000)]
        #[MapInputName('text')]
        public string $note,
        public ?int $flags = null
    ) {
    }
}

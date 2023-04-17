<?php

namespace App\Domain\Company\DataTransferObjects;

use App\Domain\Contact\Models\Contact;
use Illuminate\Validation\Rule;
use Spatie\LaravelData\Attributes\MapName;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Mappers\SnakeCaseMapper;
use Spatie\LaravelData\Optional;
use Symfony\Component\Validator\Constraints\Uuid;

#[MapName(SnakeCaseMapper::class)]
final class AttachCompanyContactNoBackrefData extends Data
{
    public function __construct(
        #[Required, Uuid]
        public readonly string $id,
        public readonly bool|Optional $isDefault,
    ) {
    }

    public static function rules(...$args): array
    {
        return [
            'id' => [Rule::exists(Contact::class)->withoutTrashed()],
        ];
    }
}

<?php

namespace App\Domain\Contact\DataTransferObjects;

use App\Domain\Contact\Enum\GenderEnum;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class CreateContactData extends Data
{
    public function __construct(
        public readonly string $sales_unit_id,
        public readonly string|Optional $language_id,
        public readonly string|Optional $address_id,
        public readonly string $contact_type,
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly ?string $phone,
        public readonly ?string $mobile,
        public readonly ?string $email,
        public readonly ?string $job_title,
        public readonly ?UploadedFile $picture,
        public readonly bool $is_verified,
        #[DataCollectionOf(CreateContactCompanyRelationNoBackrefData::class)]
        public readonly DataCollection|Optional $company_relations,
        public readonly GenderEnum|Optional $gender = GenderEnum::Unknown,
    ) {
    }
}

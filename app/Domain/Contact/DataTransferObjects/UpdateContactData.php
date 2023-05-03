<?php

namespace App\Domain\Contact\DataTransferObjects;

use App\Domain\Contact\Enum\GenderEnum;
use Illuminate\Http\UploadedFile;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class UpdateContactData extends Data
{
    public function __construct(
        public readonly string|Optional $sales_unit_id,
        public readonly string|Optional $language_id,
        public readonly string|Optional $address_id,
        public readonly string|Optional $contact_type,
        public readonly string|Optional $first_name,
        public readonly string|Optional $last_name,
        public readonly string|null|Optional $phone,
        public readonly string|null|Optional $mobile,
        public readonly string|null|Optional $email,
        public readonly string|null|Optional $job_title,
        public readonly UploadedFile|null|Optional $picture,
        public readonly bool|Optional $is_verified,
        #[DataCollectionOf(CreateContactCompanyRelationNoBackrefData::class)]
        public readonly DataCollection|Optional $company_relations,
        public readonly GenderEnum|Optional $gender,
    ) {
    }
}

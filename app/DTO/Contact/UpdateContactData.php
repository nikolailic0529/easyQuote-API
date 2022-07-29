<?php

namespace App\DTO\Contact;

use App\DTO\MissingValue;
use App\Enum\GenderEnum;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateContactData extends DataTransferObject
{
    #[Constraints\Uuid]
    public string $sales_unit_id;
    /** @var string|\App\DTO\MissingValue */
    public string|MissingValue $address_id;
    public string $contact_type;
    public GenderEnum $gender;
    public string $first_name;
    public string $last_name;
    public ?string $phone;
    public ?string $mobile;
    public ?string $email;
    public ?string $job_title;
    public ?UploadedFile $picture;
    public bool $is_verified;

    protected function parseArray(array $array): array
    {
        return array_filter(
            parent::parseArray($array),
            static fn (mixed $value): bool => !$value instanceof MissingValue
        );
    }
}
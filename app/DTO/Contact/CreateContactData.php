<?php

namespace App\DTO\Contact;

use App\DTO\Enum\DataTransferValueOption;
use App\Enum\GenderEnum;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

final class CreateContactData extends DataTransferObject
{
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

    /** @var array|\App\DTO\Enum\DataTransferValueOption */
    public array|DataTransferValueOption $addresses = DataTransferValueOption::Miss;
}
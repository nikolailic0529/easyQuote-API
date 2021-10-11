<?php

namespace App\DTO\Contact;

use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

final class UpdateContactData extends DataTransferObject
{
    public string $contact_type;

    public string $first_name;

    public string $last_name;

    public ?string $phone;

    public ?string $mobile;

    public ?string $email;

    public ?string $job_title;

    public ?UploadedFile $picture;

    public bool $is_verified;
}
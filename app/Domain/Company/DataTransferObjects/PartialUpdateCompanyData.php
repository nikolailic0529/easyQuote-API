<?php

namespace App\Domain\Company\DataTransferObjects;

use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

final class PartialUpdateCompanyData extends DataTransferObject
{
    public string $sales_unit_id;

    public string $name;

    public ?UploadedFile $logo;

    public bool $delete_logo;

    public ?string $email;

    public ?string $phone;

    public ?string $website;

    /**
     * @var \App\Domain\Company\DataTransferObjects\AttachCompanyAddressData[]|null
     */
    public ?array $addresses;

    /**
     * @var \App\Domain\Company\DataTransferObjects\AttachCompanyContactData[]|null
     */
    public ?array $contacts;
}

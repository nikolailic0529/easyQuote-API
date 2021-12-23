<?php

namespace App\DTO\Company;

use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;

final class PartialUpdateCompanyData extends DataTransferObject
{
    public string $name;

    public ?string $vat;

    /**
     * @Constraints\Choice({"EXEMPT", "NO VAT", "VAT Number"})
     *
     * @var string
     */
    public string $vat_type;

    public ?UploadedFile $logo;

    public bool $delete_logo;

    public ?string $email;

    public ?string $phone;

    public ?string $website;

    /**
     * @var \App\DTO\Company\AttachCompanyAddressData[]|null
     */
    public ?array $addresses;

    /**
     * @var \App\DTO\Company\AttachCompanyContactData[]|null
     */
    public ?array $contacts;
}

<?php

namespace App\DTO\Company;

use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateCompanyData extends DataTransferObject
{
    public string $name;

    public ?string $vat;

    /**
     * @Constraints\Choice({"EXEMPT", "NO VAT", "VAT Number"})
     *
     * @var string
     */
    public string $vat_type;

    public ?string $type;

    public ?string $source;

    public ?string $short_code;

    public ?UploadedFile $logo;

    public ?string $category;

    public ?string $email;

    public ?string $phone;

    public ?string $website;

    /**
     * @Constraints\All(@Constraints\Uuid)
     *
     * @var array
     */
    public array $vendors;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $default_vendor_id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $default_template_id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $default_country_id;

    /**
     * @var \App\DTO\Company\AttachCompanyAddressData[]
     */
    public array $addresses;

    /**
     * @var \App\DTO\Company\AttachCompanyContactData[]
     */
    public array $contacts;
}

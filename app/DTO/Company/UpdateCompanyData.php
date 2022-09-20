<?php

namespace App\DTO\Company;

use App\DTO\MissingValue;
use App\Enum\CustomerTypeEnum;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateCompanyData extends DataTransferObject
{
    #[Constraints\Uuid]
    public string $sales_unit_id;

    public string $name;

    public ?string $vat;

    #[Constraints\Choice(choices: ["EXEMPT", "NO VAT", "VAT Number"])]
    public string $vat_type;

    public ?string $type;

    public ?string $source;

    public ?string $short_code;

    public ?UploadedFile $logo;

    public bool $delete_logo;

    public ?string $category;

    public array $categories;

    /** @var \App\Enum\CustomerTypeEnum|\App\DTO\MissingValue|null */
    public CustomerTypeEnum|MissingValue|null $customer_type;

    public ?string $email;

    public ?string $phone;

    public ?string $website;

    #[Constraints\All(new Constraints\Uuid())]
    public array $vendors;

    #[Constraints\Uuid]
    public ?string $default_vendor_id;

    #[Constraints\Uuid]
    public ?string $default_template_id;

    #[Constraints\Uuid]
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

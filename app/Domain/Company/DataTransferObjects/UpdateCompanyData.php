<?php

namespace App\Domain\Company\DataTransferObjects;

use App\Domain\Company\Enum\CustomerTypeEnum;
use App\Foundation\DataTransferObject\MissingValue;
use Illuminate\Http\UploadedFile;
use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateCompanyData extends DataTransferObject
{
    #[Constraints\Uuid]
    public string $sales_unit_id;

    public string $name;

    public ?string $vat;

    #[Constraints\Choice(choices: ['EXEMPT', 'NO VAT', 'VAT Number'])]
    public string $vat_type;

    public ?string $type;

    public ?string $source;

    public ?string $short_code;

    public ?UploadedFile $logo;

    public bool $delete_logo;

    public ?string $category;

    public array $categories;

    /** @var \App\Domain\Company\Enum\CustomerTypeEnum|\App\Foundation\DataTransferObject\MissingValue|null  */
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
     * @var \App\Domain\Company\DataTransferObjects\AttachCompanyAddressData[]
     */
    public array $addresses;

    /**
     * @var \App\Domain\Company\DataTransferObjects\AttachCompanyContactData[]
     */
    public array $contacts;
}

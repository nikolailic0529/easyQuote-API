<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitOrderCustomerData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     */
    public string $customer_name;

    public string $email;

    /**
     * @Constraints\Currency
     */
    public string $currency_code;

    public ?string $phone_no;

    public ?string $fax_no;

    public ?string $company_reg_no;

    public ?string $vat_reg_no;
}

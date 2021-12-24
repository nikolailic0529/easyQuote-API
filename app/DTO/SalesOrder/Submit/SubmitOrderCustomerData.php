<?php

namespace App\DTO\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitOrderCustomerData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $customer_name;

    public string $email;

    /**
     * @Constraints\Currency
     *
     * @var string
     */
    public string $currency_code;

    public ?string $phone_no;

    public ?string $fax_no;

    public ?string $company_reg_no;

    public ?string $vat_reg_no;
}

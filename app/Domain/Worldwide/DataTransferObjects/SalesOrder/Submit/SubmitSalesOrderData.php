<?php

namespace App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank(message="At least one address must be present.")
     * @Constraints\All(@Constraints\Type("\App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderAddressData"))
     *
     * @var \App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderAddressData[]
     */
    public array $addresses_data;

    public SubmitOrderCustomerData $customer_data;

    /**
     * @Constraints\NotBlank(message="At least one order line must be present.")
     * @Constraints\All(@Constraints\Type("\App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderLineData"))
     *
     * @var \App\Domain\Worldwide\DataTransferObjects\SalesOrder\Submit\SubmitOrderLineData[]
     */
    public array $order_lines_data;

    public string $vendor_short_code;

    /**
     * @Constraints\Choice({"Pack", "Contract"}, message="Contract type must be either [Pack] or [Contract].")
     */
    public string $contract_type;

    public ?string $registration_customer_name;

    /**
     * @Constraints\Currency
     */
    public string $currency_code;

    /**
     * @Constraints\Date
     */
    public ?string $from_date;

    /**
     * @Constraints\Date
     */
    public ?string $to_date;

    public ?string $service_agreement_id;

    /**
     * @Constraints\NotBlank
     */
    public string $order_no;

    /**
     * @Constraints\Date
     */
    public string $order_date;

    /**
     * @Constraints\Choice({
     *     "SWH",
     *     "EPD",
     *     "EPD%20IT%20Services%20LLC",
     *     "EPD%20Asia%20Pacific%20Pty%20Ltd",
     *     "TESFR",
     *     "TESDE",
     *     "TESAT",
     *     "TESTS",
     *     "TESTES"
     * })
     */
    public string $bc_company_name;

    /**
     * @Constraints\Uuid
     */
    public string $company_id;

    public string $sales_person_name;

    /**
     * @Constraints\NotNull(message="Exchange rate is missing.")
     */
    public ?float $exchange_rate;

    /**
     * @Constraints\Uuid
     */
    public string $post_sales_id;

    public string $customer_po;
}

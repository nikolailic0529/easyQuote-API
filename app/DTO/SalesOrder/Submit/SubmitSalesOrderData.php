<?php

namespace App\DTO\SalesOrder\Submit;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class SubmitSalesOrderData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     * @Constraints\All(@Constraints\Type("\App\DTO\SalesOrder\Submit\SubmitOrderAddressData"))
     *
     * @var \App\DTO\SalesOrder\Submit\SubmitOrderAddressData[]
     */
    public array $addresses_data;

    public SubmitOrderCustomerData $customer_data;

    /**
     * @Constraints\NotBlank
     * @Constraints\All(@Constraints\Type("\App\DTO\SalesOrder\Submit\SubmitOrderLineData"))
     *
     * @var \App\DTO\SalesOrder\Submit\SubmitOrderLineData[]
     */
    public array $order_lines_data;

    /**
     * @Constraints\Choice({"HPE", "LEN", "IBM"})
     *
     * @var string
     */
    public string $vendor_short_code;

    /**
     * @Constraints\Choice({"Pack", "Contract"})
     *
     * @var string
     */
    public string $contract_type;

    public ?string $registration_customer_name;

    /**
     * @Constraints\Currency
     *
     * @var string
     */
    public string $currency_code;

    /**
     * @Constraints\Date
     *
     * @var string|null
     */
    public ?string $from_date;

    /**
     * @Constraints\Date
     *
     * @var string|null
     */
    public ?string $to_date;

    /**
     * @var string|null
     */
    public ?string $service_agreement_id;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $order_no;

    /**
     * @Constraints\Date
     *
     * @var string
     */
    public string $order_date;

    /**
     * @Constraints\Choice({"SWH"})
     *
     * @var string
     */
    public string $bc_company_name;

    public string $sales_person_name;

    /**
     * @Constraints\NotNull(message="Exchange rate is missing.")
     *
     * @var float|null
     */
    public ?float $exchange_rate;

    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $post_sales_id;

    public string $customer_po;
}

<?php

namespace App\Domain\VendorServices\Services\Models;

use Spatie\DataTransferObject\DataTransferObject;

final class CheckSalesOrderResultBcOrder extends DataTransferObject
{
    protected bool $ignoreMissing = true;

    public string $id;

    public ?string $company_id;

    public string $customer_id;

    public ?string $vendor_id;

    public string $post_sales_id;

    public ?string $bc_order_id;

    public ?string $bc_odata_etag;

    public string $order_no;

    public ?string $bc_order_no;

    public string $order_date;

    public ?string $SAID;

    public ?string $from_date;

    public ?string $to_date;

    public ?string $registration_customer_name;

    public ?string $registration_customer_email;

    public ?string $registration_customer_phone_no;

    public ?string $customer_po;

    public ?string $currency_code;

    public ?float $exchange_rate;

    public ?string $sales_person_name;

    public ?string $bc_company;

    public ?string $user_agent;

    public ?string $in_contract;

    public ?int $status;

    public ?string $status_reason;

    public string $created_at;

    public string $updated_at;

    /** @var \App\Domain\VendorServices\Services\Models\CheckSalesOrderResultBcSalesLine[] */
    public array $bc_sales_line;

    /** @var \App\Domain\VendorServices\Services\Models\CheckSalesOrderResultBcAddress[] */
    public array $bc_addresses;
}

<?php

namespace App\Services\VendorServices\Models;

use Spatie\DataTransferObject\DataTransferObject;

final class CheckSalesOrderResult extends DataTransferObject
{
    public string $id;

    public ?string $bc_customer_id;

    public string $bc_company;

    public ?string $bc_odata_etag;

    public string $customer_number;

    public string $customer_name;

    public ?string $customer_reg_no;

    public ?string $vat_reg_no;

    public ?string $contact_name;

    public ?string $phone_no;

    public ?string $fax_no;

    public ?string $email;

    public ?string $currency_code;

    public ?string $language_code;

    public ?string $iasset_language_code;

    public ?string $payment_terms_days;

    public ?string $delivery_method;

    public ?int $avatax;

    public int $status;

    public int $is_supplier;

    public string $created_at;

    public string $updated_at;

    public ?string $deleted_at;

    /** @var \App\Services\VendorServices\Models\CheckSalesOrderResultBcOrder[]  */
    public array $bc_orders;
}
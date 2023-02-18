<?php

namespace App\Domain\VendorServices\Services\Models;

use Spatie\DataTransferObject\DataTransferObject;

final class CheckSalesOrderResultBcSalesLine extends DataTransferObject
{
    protected bool $ignoreMissing = true;

    public string $id;

    public ?string $bc_item_id;

    public ?string $bc_odata_etag;

    public ?string $account_no;

    public ?string $bc_account_id;

    public ?string $vat_code;

    public ?string $service_sku;

    public ?string $service_description;

    public ?string $quantity;

    public ?string $serial_number;

    public ?string $sku;

    public ?string $product_description;

    public ?float $unit_price;

    public ?float $buy_price;

    public ?float $discount_percentage;

    public ?string $buy_currency_code;

    public ?string $machine_country_code;

    public ?string $distributor;

    public int $drop_shipment;

    public ?float $vat_amount;

    public ?int $discount_applied;

    public ?string $purchase_order_no;

    public ?string $vendor;

    public ?string $vendor_quote_number;

    public ?string $iasset_vendor_entity_name;

    public ?string $business_type;

    public ?string $sales_group;

    public ?string $current_warranty_end;

    public ?string $updated_warranty_end;

    public ?string $invalid_id;

    public string $bc_order_id;

    public ?int $status;

    public string $created_at;

    public string $updated_at;

    public ?string $deleted_at;
}

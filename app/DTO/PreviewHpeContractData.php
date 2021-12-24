<?php

namespace App\DTO;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\DataTransferObject;

class PreviewHpeContractData extends DataTransferObject
{
    public ?string $amp_id, $contract_number, $purchase_order_date, $purchase_order_no, $hpe_sales_order_no, $contract_date;

    public Collection $contract_details, $contract_assets, $service_overview, $support_account_reference, $asset_locations, $serial_numbers, $support_services;

    public HpeContractContact $hw_delivery_contact, $sw_delivery_contact, $pr_support_contact, $entitled_party_contact, $end_customer_contact, $sold_contact, $bill_contact;

    /** @var string[] */
    public array $images = [];

    /** @var string[] */
    public array $translations = [];

    protected array $exceptKeys = ['images', 'translations'];
}

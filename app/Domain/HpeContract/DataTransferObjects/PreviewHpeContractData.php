<?php

namespace App\Domain\HpeContract\DataTransferObjects;

use Illuminate\Support\Collection;
use Spatie\DataTransferObject\DataTransferObject;

class PreviewHpeContractData extends DataTransferObject
{
    public ?string $amp_id;
    public ?string $contract_number;
    public ?string $purchase_order_date;
    public ?string $purchase_order_no;
    public ?string $hpe_sales_order_no;
    public ?string $contract_date;

    public Collection $contract_details;
    public Collection $contract_assets;
    public Collection $service_overview;
    public Collection $support_account_reference;
    public Collection $asset_locations;
    public Collection $serial_numbers;
    public Collection $support_services;

    public HpeContractContact $hw_delivery_contact;
    public HpeContractContact $sw_delivery_contact;
    public HpeContractContact $pr_support_contact;
    public HpeContractContact $entitled_party_contact;
    public HpeContractContact $end_customer_contact;
    public HpeContractContact $sold_contact;
    public HpeContractContact $bill_contact;

    /** @var string[] */
    public array $images = [];

    /** @var string[] */
    public array $translations = [];

    protected array $exceptKeys = ['images', 'translations'];
}

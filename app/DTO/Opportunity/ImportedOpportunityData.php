<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;

final class ImportedOpportunityData extends DataTransferObject
{
    public string $id;

    public ?string $contract_type_id;

    public ?string $opportunity_type;

    public ?string $company_id;

    public ?string $account_name;

    public ?string $account_manager_name;

    public ?float $opportunity_amount;

    public string $opportunity_start_date;

    public string $opportunity_end_date;

    public string $opportunity_closing_date;

    public ?string $sale_action_name;

    public ?string $project_name;

    public string $created_at;
}

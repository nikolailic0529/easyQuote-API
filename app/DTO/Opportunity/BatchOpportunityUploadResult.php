<?php


namespace App\DTO\Opportunity;


use Spatie\DataTransferObject\DataTransferObject;

final class BatchOpportunityUploadResult extends DataTransferObject
{
    /**
     * @var \App\DTO\Opportunity\ImportedOpportunityData[]
     */
    public array $opportunities;

    /**
     * @var string[]
     */
    public array $errors;
}

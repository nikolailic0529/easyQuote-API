<?php


namespace App\DTO\Opportunity;


use Spatie\DataTransferObject\DataTransferObject;

final class BatchOpportunityUploadResult extends DataTransferObject
{
    /** @var \App\Models\Opportunity[] */
    public array $opportunities;

    /**
     * @var string[]
     */
    public array $errors;
}

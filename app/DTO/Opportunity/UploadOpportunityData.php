<?php

namespace App\DTO\Opportunity;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class UploadOpportunityData extends DataTransferObject
{
    public UploadedFile $opportunities_file;

    public ?UploadedFile $accounts_data_file;

    public ?UploadedFile $account_contacts_file;
}

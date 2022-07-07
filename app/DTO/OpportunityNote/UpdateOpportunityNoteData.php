<?php

namespace App\DTO\OpportunityNote;

use Spatie\DataTransferObject\DataTransferObject;

final class UpdateOpportunityNoteData extends DataTransferObject
{
    public string $text;
}
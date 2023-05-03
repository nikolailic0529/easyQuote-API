<?php

namespace App\Domain\Note\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class CreateOpportunityNoteData extends DataTransferObject
{
    public string $opportunity_id;

    public string $text;
}

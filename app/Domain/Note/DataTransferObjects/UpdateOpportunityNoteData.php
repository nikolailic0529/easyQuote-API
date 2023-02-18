<?php

namespace App\Domain\Note\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class UpdateOpportunityNoteData extends DataTransferObject
{
    public string $text;
}

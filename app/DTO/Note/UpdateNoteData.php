<?php

namespace App\DTO\Note;

use Spatie\DataTransferObject\DataTransferObject;

final class UpdateNoteData extends DataTransferObject
{
    public string $note;
}
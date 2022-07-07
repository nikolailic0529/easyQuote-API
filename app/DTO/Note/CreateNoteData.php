<?php

namespace App\DTO\Note;

use Spatie\DataTransferObject\DataTransferObject;

final class CreateNoteData extends DataTransferObject
{
    public string $note;
}
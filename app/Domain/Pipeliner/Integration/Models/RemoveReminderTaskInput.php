<?php

namespace App\Domain\Pipeliner\Integration\Models;

class RemoveReminderTaskInput extends BaseInput
{
    public function __construct(public readonly string $id)
    {
    }
}

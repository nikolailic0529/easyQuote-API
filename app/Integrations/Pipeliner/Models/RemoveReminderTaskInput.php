<?php

namespace App\Integrations\Pipeliner\Models;

class RemoveReminderTaskInput extends BaseInput
{
    public function __construct(public readonly string $id)
    {
    }
}
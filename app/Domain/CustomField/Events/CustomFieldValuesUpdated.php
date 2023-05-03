<?php

namespace App\Domain\CustomField\Events;

use App\Domain\CustomField\Models\CustomField;
use Illuminate\Queue\SerializesModels;

final class CustomFieldValuesUpdated
{
    use SerializesModels;

    public function __construct(public readonly CustomField $customField)
    {
    }
}

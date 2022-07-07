<?php

namespace App\Events\CustomField;

use App\Models\System\CustomField;
use Illuminate\Queue\SerializesModels;

final class CustomFieldValuesUpdated
{
    use SerializesModels;

    public function __construct(public readonly CustomField $customField)
    {
    }
}

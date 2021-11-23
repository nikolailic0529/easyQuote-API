<?php

namespace App\DTO\WorldwideQuote\Export;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class AssetField extends DataTransferObject
{
    /**
     * @Constraints\Choice({"product_no", "description", "serial_no", "date_from", "contract_duration", "qty", "price", "pricing_document", "system_handle", "searchable", "service_level_description"})
     */
    public string $field_name;

    /**
     * @Constraints\NotBlank
     */
    public string $field_header;
}

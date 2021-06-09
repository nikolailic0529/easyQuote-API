<?php

namespace App\DTO\MappedRow;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

final class MappedRowFieldData extends DataTransferObject implements GroupSequenceProviderInterface
{
    /**
     * @Constraints\Choice({
     *     "product_no",
     *     "service_sku",
     *     "description",
     *     "serial_no",
     *     "date_from",
     *     "date_to",
     *     "qty",
     *     "price",
     *     "original_price",
     *     "pricing_document",
     *     "searchable",
     *     "service_level_description"
     * })
     *
     * @var string
     */
    public string $field_name;

    /**
     * @Constraints\Type(type={"string", "null"}, groups={"product_no", "service_sku", "description", "serial_no", "date_from", "date_to", "pricing_document", "searchable", "service_level_description"})
     * @Constraints\Type(type={"integer", "null"}, groups={"qty"})
     * @Constraints\Type(type={"numeric", "null"}, groups={"price", "original_price"})
     * @Constraints\Date(groups={"date_from", "date_to"})
     *
     * @var string|integer|float|null
     */
    public $field_value;

    public function getGroupSequence(): array
    {
        return [
            // Include the "Client" group to validate the $type property as well.
            // Note that using the "Default" group here won't work!
            'MappedRowFieldData',
            // Use either the person or company group based on the selected type.
            $this->field_name,
        ];
    }
}

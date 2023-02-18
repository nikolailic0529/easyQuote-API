<?php

namespace App\Domain\DocumentMapping\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\Validator\GroupSequenceProviderInterface;

final class MappedRowFieldData extends DataTransferObject implements GroupSequenceProviderInterface
{
    #[Constraints\Choice(
        choices: [
            'product_no',
            'service_sku',
            'description',
            'serial_no',
            'date_from',
            'date_to',
            'qty',
            'price',
            'original_price',
            'pricing_document',
            'searchable',
            'service_level_description',
            'is_serial_number_generated',
            'machine_address_id',
        ]
    )]
    public string $field_name;

    /**
     * @var string|int|float|bool|null
     */
    #[Constraints\Type(type: ['string', 'null'], groups: ['product_no', 'service_sku', 'description', 'serial_no', 'date_from', 'date_to', 'pricing_document', 'searchable', 'service_level_description', 'machine_address_id'])]
    #[Constraints\Uuid(groups: ['machine_address_id'])]
    #[Constraints\Type(type: ['integer', 'null'], groups: ['city'])]
    #[Constraints\Type(type: ['numeric', 'null'], groups: ['price', 'original_price'])]
    #[Constraints\Type(type: ['boolean', 'null'], groups: ['is_serial_number_generated'])]
    #[Constraints\Date(groups: ['date_from', 'date_to'])]
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

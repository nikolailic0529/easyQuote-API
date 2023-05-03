<?php

namespace App\Domain\UnifiedContract\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;

final class ContractLookupQueryData extends DataTransferObject
{
    public ?string $search_query = null;

    /**
     * @var \App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\FieldValue[]
     */
    public array $should_equal_fields = [];

    /**
     * @var \App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\TermValue[]
     */
    public array $term_equal_values = [];

    /**
     * @var \App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\TermValue[]
     */
    public array $term_not_equal_values = [];

    /**
     * @var \App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\FieldValue[]
     */
    public array $must_not_equal_fields = [];

    /**
     * @var \App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\FieldValue[]
     */
    public array $must_equal_fields = [];

    /**
     * @var \App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\FieldValue[]
     */
    public array $range_gte_fields = [];

    /**
     * @var \App\Domain\UnifiedContract\DataTransferObjects\EntityFilter\FieldValue[]
     */
    public array $range_lte_fields = [];
}

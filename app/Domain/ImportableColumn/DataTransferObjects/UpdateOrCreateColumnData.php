<?php

namespace App\Domain\ImportableColumn\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateOrCreateColumnData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $id;

    /**
     * @Constraints\Uuid
     */
    public ?string $de_header_reference;

    /**
     * @Constraints\Choice({"text", "number", "decimal", "date"})
     */
    public string $type;

    /**
     * @Constraints\NotBlank
     */
    public string $name;

    /**
     * @Constraints\NotBlank
     */
    public string $header;

    /**
     * @Constraints\Uuid
     */
    public ?string $country_id;

    public int $order;

    public bool $is_system;

    public bool $is_temp;

    /**
     * @Constraints\All(@Constraints\NotBlank)
     *
     * @var string[]
     */
    public array $aliases;
}

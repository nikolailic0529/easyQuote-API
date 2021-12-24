<?php

namespace App\DTO\ImportableColumn;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateOrCreateColumnData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $id;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $de_header_reference;

    /**
     * @Constraints\Choice({"text", "number", "decimal", "date"})
     *
     * @var string
     */
    public string $type;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $name;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $header;

    /**
     * @Constraints\Uuid
     *
     * @var string|null
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
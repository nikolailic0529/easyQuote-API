<?php

namespace App\Services\DocumentEngine\Models;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateDocumentHeaderData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $headerReference;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $headerName;

    /**
     * @Constraints\All(@Constraints\NotBlank)
     *
     * @var string[]
     */
    public array $headerAliases;
}
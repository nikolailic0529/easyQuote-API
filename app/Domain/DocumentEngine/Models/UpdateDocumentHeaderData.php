<?php

namespace App\Domain\DocumentEngine\Models;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdateDocumentHeaderData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $headerReference;

    /**
     * @Constraints\NotBlank
     */
    public string $headerName;

    /**
     * @Constraints\All(@Constraints\NotBlank)
     *
     * @var string[]
     */
    public array $headerAliases;
}

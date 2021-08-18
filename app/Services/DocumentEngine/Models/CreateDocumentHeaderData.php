<?php

namespace App\Services\DocumentEngine\Models;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class CreateDocumentHeaderData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $headerName;

    /**
     * @Constraints\All(
     *     @Constraints\NotBlank
     * )
     * @Constraints\Unique
     *
     *
     * @var string[]
     */
    public array $headerAliases;
}
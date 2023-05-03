<?php

namespace App\Domain\Asset\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class AssetsGroupData extends DataTransferObject
{
    /**
     * @Constraints\NotBlank()
     */
    public string $group_name;

    /**
     * @Constraints\NotBlank()
     */
    public string $search_text;

    /**
     * @Constraints\All(@Constraints\Uuid())
     *
     * @var string[]
     */
    public array $assets;
}

<?php

namespace App\Domain\Worldwide\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

class RowsGroupData extends DataTransferObject
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
    public array $rows;
}

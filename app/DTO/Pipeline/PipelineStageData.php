<?php

namespace App\DTO\Pipeline;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PipelineStageData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $stage_id = null;

    public int $stage_order;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $stage_name;
}

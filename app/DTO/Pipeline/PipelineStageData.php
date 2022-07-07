<?php

namespace App\DTO\Pipeline;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PipelineStageData extends DataTransferObject
{
    #[Constraints\Uuid]
    public ?string $stage_id = null;

    public int $stage_order;

    #[Constraints\NotBlank]
    public string $stage_name;

    #[Constraints\PositiveOrZero]
    #[Constraints\LessThanOrEqual(100)]
    public float $stage_percentage;
}

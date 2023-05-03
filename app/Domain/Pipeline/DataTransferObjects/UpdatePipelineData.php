<?php

namespace App\Domain\Pipeline\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdatePipelineData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public string $space_id;

    /**
     * @Constraints\NotBlank
     */
    public string $pipeline_name;

    /**
     * @var \App\Domain\Pipeline\DataTransferObjects\PipelineStageData[]
     */
    public array $pipeline_stages;

    public bool $is_default = false;

    public int $pipeline_order = 0;
}

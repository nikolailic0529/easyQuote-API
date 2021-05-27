<?php

namespace App\DTO\Pipeline;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class UpdatePipelineData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string
     */
    public string $space_id;

    /**
     * @Constraints\NotBlank
     *
     * @var string
     */
    public string $pipeline_name;

    /**
     * @var \App\DTO\Pipeline\PipelineStageData[]
     */
    public array $pipeline_stages;

    public bool $is_default = false;

    public int $pipeline_order = 0;
}

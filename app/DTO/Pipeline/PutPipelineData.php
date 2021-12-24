<?php

namespace App\DTO\Pipeline;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PutPipelineData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     *
     * @var string|null
     */
    public ?string $pipeline_id;

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

    public bool $is_default;

    /**
     * @Constraints\All(
     *     @Constraints\Type("\App\DTO\Pipeline\PipelineStageData")
     * )
     *
     * @var \App\DTO\Pipeline\PipelineStageData[]
     */
    public array $pipeline_stages;
}

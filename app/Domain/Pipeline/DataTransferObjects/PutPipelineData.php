<?php

namespace App\Domain\Pipeline\DataTransferObjects;

use Spatie\DataTransferObject\DataTransferObject;
use Symfony\Component\Validator\Constraints;

final class PutPipelineData extends DataTransferObject
{
    /**
     * @Constraints\Uuid
     */
    public ?string $pipeline_id;

    /**
     * @Constraints\Uuid
     */
    public string $space_id;

    /**
     * @Constraints\NotBlank
     */
    public string $pipeline_name;

    public bool $is_default;

    /**
     * @Constraints\All(
     *     @Constraints\Type("\App\Domain\Pipeline\DataTransferObjects\PipelineStageData")
     * )
     *
     * @var \App\Domain\Pipeline\DataTransferObjects\PipelineStageData[]
     */
    public array $pipeline_stages;
}

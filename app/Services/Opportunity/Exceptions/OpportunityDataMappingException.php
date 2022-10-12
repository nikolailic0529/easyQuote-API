<?php

namespace App\Services\Opportunity\Exceptions;

class OpportunityDataMappingException extends \Exception
{
    public static function couldNotResolvePipelinerPipeline(string $pipeline): static
    {
        return new static("Could not resolve pipeline [$pipeline] in Pipeliner.");
    }

    public static function couldNotResolvePipelinerStep(string $stage, string $pipeline): static
    {
        return new static("Could not resolve step (name: [$stage], pipeline: [$pipeline]) in Pipeliner.");
    }

    public static function distributorsOrderViolation(): static
    {
        return new static("Order of distributors violated.");
    }
}
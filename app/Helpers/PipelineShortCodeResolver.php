<?php

namespace App\Helpers;

final class PipelineShortCodeResolver
{
    public function __invoke(string $pipelineName): string
    {
        preg_match_all('/\b[a-zA-Z0-9]/', $pipelineName, $matches);

        return strtoupper(implode('', $matches[0]));
    }
}

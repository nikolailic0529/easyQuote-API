<?php

namespace App\Helpers;

final class PipelineShortCodeResolver
{
    public function __invoke(string $pipelineName): string
    {
        $value = $pipelineName;

        $value = ucwords(str_replace(['-', '_'], ' ', $value));

        preg_match_all('/[A-Z]+/', $value, $matches);

        return implode('', $matches[0]);
    }
}

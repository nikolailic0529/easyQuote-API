<?php

namespace App\Domain\DocumentProcessing\Readers\Validation;

class RowValidationPipeline
{
    protected array $pipes = [];

    public function pipe(RowValidationPipe $pipe): self
    {
        $this->pipes[] = $pipe;

        return $this;
    }

    public function process(RowValidationPayload $payload): bool
    {
        foreach ($this->pipes as $pipe) {
            $result = $pipe($payload);

            if (true === $result && $pipe instanceof TrueInterruptibleRowValidationPipe) {
                return true;
            } elseif (false === $result && !$pipe instanceof TrueInterruptibleRowValidationPipe) {
                return false;
            }
        }

        return true;
    }
}

<?php

namespace App\Domain\DocumentProcessing\Readers\Validation;

interface RowValidationPipe
{
    public function __invoke(RowValidationPayload $payload): bool;
}

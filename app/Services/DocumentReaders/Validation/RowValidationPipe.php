<?php

namespace App\Services\DocumentReaders\Validation;

interface RowValidationPipe
{
    public function __invoke(RowValidationPayload $payload): bool;
}

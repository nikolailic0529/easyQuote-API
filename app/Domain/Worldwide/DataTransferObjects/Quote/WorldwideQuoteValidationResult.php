<?php

namespace App\Domain\Worldwide\DataTransferObjects\Quote;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Support\Responsable;

final class WorldwideQuoteValidationResult implements Responsable
{
    private bool $passes;

    private MessageBag $errors;

    /**
     * WorldwideQuoteValidationResult constructor.
     */
    public function __construct(bool $passes, MessageBag $errors)
    {
        $this->passes = $passes;
        $this->errors = $errors;
    }

    public function toResponse($request)
    {
        return response()->json([
            'passes' => $this->passes,
            'errors' => array_values(array_unique($this->errors->all())),
        ]);
    }
}

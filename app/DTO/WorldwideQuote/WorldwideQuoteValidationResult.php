<?php

namespace App\DTO\WorldwideQuote;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Support\Responsable;

final class WorldwideQuoteValidationResult implements Responsable
{
    protected bool $passes;

    protected MessageBag $errors;

    /**
     * WorldwideQuoteValidationResult constructor.
     * @param bool $passes
     * @param MessageBag $errors
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
            'errors' => array_values(array_unique($this->errors->all()))
        ]);
    }
}

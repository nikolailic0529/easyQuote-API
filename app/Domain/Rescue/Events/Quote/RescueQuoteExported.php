<?php

namespace App\Domain\Rescue\Events\Quote;

use App\Domain\Rescue\Models\BaseQuote;

final class RescueQuoteExported
{
    public function __construct(protected BaseQuote $quote,
                                protected int $asType)
    {
    }

    public function getQuote(): BaseQuote
    {
        return $this->quote;
    }

    public function getAsType(): int
    {
        return $this->asType;
    }
}

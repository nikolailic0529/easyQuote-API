<?php

namespace App\Events\RescueQuote;

use App\Models\Quote\BaseQuote;

final class RescueQuoteExported
{
    public function __construct(protected BaseQuote $quote,
                                protected int       $asType)
    {
    }

    /**
     * @return BaseQuote
     */
    public function getQuote(): BaseQuote
    {
        return $this->quote;
    }

    /**
     * @return int
     */
    public function getAsType(): int
    {
        return $this->asType;
    }
}
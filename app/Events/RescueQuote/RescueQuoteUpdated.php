<?php

namespace App\Events\RescueQuote;

use App\Contracts\WithRescueQuoteEntity;
use App\Models\Quote\Quote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RescueQuoteUpdated implements WithRescueQuoteEntity
{
    use Dispatchable, SerializesModels;

    private Quote $quote;

    /**
     * Create a new event instance.
     *
     * @param Quote $quote
     */
    public function __construct(Quote $quote)
    {
        $this->quote = $quote;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }
}

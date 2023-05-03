<?php

namespace App\Domain\Rescue\Events\Quote;

use App\Domain\Rescue\Contracts\WithRescueQuoteEntity;
use App\Domain\Rescue\Models\Quote;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class RescueQuoteSubmitted implements WithRescueQuoteEntity
{
    use Dispatchable;
    use SerializesModels;

    private Quote $quote;

    /**
     * Create a new event instance.
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

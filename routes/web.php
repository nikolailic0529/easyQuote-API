<?php

use App\Contracts\Services\QuoteServiceInterface;
use App\Models\Quote\Quote;

Route::get('pdf', function () {
    $quoteService = app(QuoteServiceInterface::class);
    $quote = Quote::submitted()->orderBy('updated_at', 'desc')->firstOrFail();

    return $quoteService->export($quote);
});

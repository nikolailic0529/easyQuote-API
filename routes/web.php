<?php

use App\Contracts\Services\QuoteServiceInterface;
use App\Models\Collaboration\Invitation;
use App\Models\PasswordReset;
use App\Models\Quote\Quote;

/**
 * These routes only for development
 */
Route::get('pdf', function () {
    $quoteService = app(QuoteServiceInterface::class);
    $quote = Quote::submitted()->orderBy('updated_at', 'desc')->firstOrFail();

    return $quoteService->inlinePdf($quote, false);
});

Route::get('invitations', function () {
    return Invitation::latest()->get()->each->makeHiddenExcept(['email', 'role_name', 'url']);
});

Route::get('password-resets', function () {
    return PasswordReset::latest()->get()->each->makeHiddenExcept(['token', 'expires_at', 'is_expired', 'host', 'url']);
});

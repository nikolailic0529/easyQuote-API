<?php

use App\Http\Controllers\API\HpeContractController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideQuoteController;
use Illuminate\Support\Facades\Route;

if (config('app.debug')) {
    Route::view('recaptcha/v2', 'recaptcha.v2');
    Route::view('recaptcha/v3', 'recaptcha.v3');
    Route::get('hpe-contracts/{hpe_contract}/preview', [HpeContractController::class, 'viewHpeContract']);
    Route::get('ww-quotes/{worldwide_quote}/preview', [WorldwideQuoteController::class, 'showQuotePreview']);
}

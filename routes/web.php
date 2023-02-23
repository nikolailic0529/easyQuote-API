<?php

use App\Domain\HpeContract\Controllers\V1\HpeContractController;
use App\Domain\Rescue\Controllers\V1\QuoteSubmittedController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteController;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', static function (): View {
    return view('welcome');
});

Route::get('/dashboard', static function (): View {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

if (config('app.debug')) {
    Route::get('quotes/{submitted}/contract/preview', [QuoteSubmittedController::class, 'showContractOfQuotePreview']);
    Route::view('recaptcha/v2', 'recaptcha.v2');
    Route::view('recaptcha/v3', 'recaptcha.v3');
    Route::get('hpe-contracts/{hpe_contract}/preview', [HpeContractController::class, 'viewHpeContract']);
    Route::get('ww-quotes/{worldwide_quote}/preview', [WorldwideQuoteController::class, 'showQuotePreview']);
}

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\HpeContractController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideQuoteController;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

require __DIR__.'/auth.php';

if (config('app.debug')) {
    Route::view('recaptcha/v2', 'recaptcha.v2');
    Route::view('recaptcha/v3', 'recaptcha.v3');
    Route::get('hpe-contracts/{hpe_contract}/preview', [HpeContractController::class, 'viewHpeContract']);
    Route::get('ww-quotes/{worldwide_quote}/preview', [WorldwideQuoteController::class, 'showQuotePreview']);
}

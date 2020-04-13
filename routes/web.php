<?php

use Illuminate\Support\Facades\File;

if (!app()->environment('production')) {
    Route::view('recaptcha/v2', 'recaptcha.v2');
    Route::view('recaptcha/v3', 'recaptcha.v3');
}

<?php

if (!app()->environment('production')) {
    Route::view('recaptcha/v2', 'recaptcha.v2');
    Route::view('recaptcha/v3', 'recaptcha.v3');
    Route::get('hpe-contracts/{hpe_contract}/view', 'API\HpeContractController@viewHpeContract');
}

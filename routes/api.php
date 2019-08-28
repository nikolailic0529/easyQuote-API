<?php

use Illuminate\Http\Request;

Route::group(['prefix' => 'auth'], function () {
    Route::post('signin', 'API\AuthController@signin');
    Route::post('signup', 'API\AuthController@signup');

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('logout', 'API\AuthController@logout');
        Route::get('user', 'API\AuthController@user');
    });
});
<?php

Route::group(['namespace' => 'API'], function () {
    Route::group(['prefix' => 'auth'], function () {
        Route::post('signin', 'AuthController@signin');
        Route::post('signup', 'AuthController@signup');

        Route::group(['middleware' => 'auth:api'], function () {
            Route::get('logout', 'AuthController@logout');
            Route::get('user', 'AuthController@user');
        });
    });

    Route::group(['prefix' => 'data', 'namespace' => 'Data'], function () {
        Route::get('countries', 'CountriesController');
        Route::get('timezones', 'TimezonesController');
        Route::get('languages', 'LanguagesController');
        Route::get('currencies', 'CurrenciesController');
        Route::get('fileformats', 'FileFormatsController');
    });

    Route::group(['prefix' => 'quotes', 'middleware' => 'auth:api', 'namespace' => 'Quotes'], function () {
        Route::post('state', 'QuoteController@storeState');

        Route::post('file', 'QuoteFilesController@store');
        Route::get('file/{quoteFile}', 'QuoteFilesController@file');
        Route::get('files', 'QuoteFilesController@all');
        Route::post('handle', 'QuoteFilesController@handle');

        Route::get('customers', 'QuoteController@customers');

        Route::group(['prefix' => 'step'], function () {
            Route::get('1', 'QuoteController@step1');
            Route::post('1', 'QuoteController@templates');

            Route::post('2', 'QuoteController@step2');
        });
    });
});

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
        Route::get('/get/{quote}', 'QuoteController@quote');
        Route::post('state', 'QuoteController@storeState');

        /**
         * User's drafted Quotes
         */
        Route::get('drafted', 'QuoteController@drafted');

        Route::post('file', 'QuoteFilesController@store');
        Route::get('file/{quoteFile}', 'QuoteFilesController@file');
        Route::get('files', 'QuoteFilesController@all');
        Route::post('handle', 'QuoteFilesController@handle');

        /**
         * Get existing (S4) Customers
         */
        Route::get('customers', 'QuoteController@customers');
        Route::get('customers/{customer}', 'QuoteController@customer');

        Route::group(['prefix' => 'step'], function () {
            /**
             * Data Select Separators, Companies
             */
            Route::get('1', 'QuoteController@step1');

            /**
             * Get Templates by Company, Country, Vendor ids
             */
            Route::post('1', 'QuoteController@templates');

            /**
             * Set Margin Dialog
             */
            Route::get('3', 'QuoteController@step3');
        });
    });
});

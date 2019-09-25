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
        $crdRoutes = ['index', 'show', 'destroy'];
        $crRoutes = ['index', 'show', 'store'];
        $rRoutes = ['index', 'show'];

        Route::get('/get/{quote}', 'QuoteController@quote');
        Route::post('state', 'QuoteController@storeState');

        /**
         * User's drafted Quotes
         */
        Route::resource('drafted', 'QuoteDraftedController', ['only' => $crdRoutes]);

        Route::resource('file', 'QuoteFilesController', ['only' => $crRoutes]);
        Route::post('handle', 'QuoteFilesController@handle');

        /**
         * S4 Customers
         */
        Route::resource('customers', 'CustomerController', ['only' => $rRoutes]);

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
             * Mapping Review
             */
            Route::post('2', 'QuoteController@step2');

            /**
             * Set Margin Dialog
             */
            Route::get('3', 'QuoteController@step3');

            /**
             * Get Quote Rows Data with Applied Margin
             */
            Route::post('4', 'QuoteController@step4');
        });
    });
});

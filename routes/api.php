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

    Route::group(['middleware' => 'auth:api'], function () {
        Route::group([], function () {
            Route::apiResource('vendors', 'VendorController');
            Route::put('vendors/activate/{vendor}', 'VendorController@activate');
            Route::put('vendors/deactivate/{vendor}', 'VendorController@deactivate');
            Route::get('vendors/country/{country}', 'VendorController@country');
        });

        Route::group(['namespace' => 'Margins'], function () {
            Route::apiResource('margins', 'CountryMarginController');
            Route::put('margins/activate/{margin}', 'CountryMarginController@activate');
            Route::put('margins/deactivate/{margin}', 'CountryMarginController@deactivate');
            Route::post('margins/percentages', 'CountryMarginController@percentages');
        });

        Route::group(['namespace' => 'Discounts', 'prefix' => 'discounts'], function () {
            Route::apiResource('multi_year', 'MultiYearDiscountController');
            Route::put('multi_year/activate/{multi_year}', 'MultiYearDiscountController@activate');
            Route::put('multi_year/deactivate/{multi_year}', 'MultiYearDiscountController@deactivate');

            Route::apiResource('pre_pay', 'PrePayDiscountController');
            Route::put('pre_pay/activate/{pre_pay}', 'PrePayDiscountController@activate');
            Route::put('pre_pay/deactivate/{pre_pay}', 'PrePayDiscountController@deactivate');

            Route::apiResource('promotions', 'PromotionalDiscountController');
            Route::put('promotions/activate/{promotions}', 'PromotionalDiscountController@activate');
            Route::put('promotions/deactivate/{promotions}', 'PromotionalDiscountController@deactivate');

            Route::apiResource('snd', 'SNDcontroller');
            Route::put('snd/activate/{snd}', 'SNDcontroller@activate');
            Route::put('snd/deactivate/{snd}', 'SNDcontroller@deactivate');
        });

        Route::group(['prefix' => 'quotes', 'namespace' => 'Quotes'], function () {
            Route::get('/get/{quote}', 'QuoteController@quote');
            Route::get('/discounts/{quote}', 'QuoteController@discounts');
            Route::post('state', 'QuoteController@storeState');

            /**
             * User's drafted Quotes
             */
            Route::apiResource('drafted', 'QuoteDraftedController', ['only' => config('route.rd')]);
            Route::patch('drafted/{quote}', 'QuoteDraftedController@activate');
            Route::put('drafted/{quote}', 'QuoteDraftedController@deactivate');

            Route::apiResource('file', 'QuoteFilesController', ['only' => config('route.cr')]);
            Route::post('handle', 'QuoteFilesController@handle');

            /**
             * S4 Customers
             */
            Route::apiResource('customers', 'CustomerController', ['only' => config('route.r')]);

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
});

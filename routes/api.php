<?php

Route::group(['namespace' => 'API'], function () {
    Route::group(['prefix' => 'auth', 'middleware' => 'throttle:120,1'], function () {
        Route::post('signin', 'AuthController@signin')->name('signin');
        Route::post('signup', 'AuthController@signup')->name('signup');
        Route::get('signup/{invitation}', 'AuthController@invitation');
        Route::post('signup/{invitation}', 'AuthController@signupCollaborator');

        Route::group(['middleware' => 'auth:api'], function () {
            Route::get('logout', 'AuthController@logout');
            Route::get('user', 'AuthController@user');
        });
    });

    Route::group(['prefix' => 'data', 'namespace' => 'Data'], function () {
        Route::group(['middleware' => 'throttle:60,1'], function () {
            Route::get('timezones', 'TimezonesController');
            Route::get('languages', 'LanguagesController');
            Route::get('currencies', 'CurrenciesController');
            Route::get('fileformats', 'FileFormatsController');
        });
        Route::get('countries', 'CountriesController'); // exclusive high throttle rate
    });

    Route::group(['middleware' => 'auth:api'], function () {
        Route::group(['middleware' => 'throttle:60,1'], function () {
            Route::resource('users', 'UserController', ['only' => config('route.crud')]);
            Route::put('users/activate/{user}', 'UserController@activate');
            Route::put('users/deactivate/{user}', 'UserController@deactivate');
        });

        Route::group(['middleware' => 'throttle:60,1'], function () {
            Route::resource('roles', 'RoleController', ['only' => config('route.crud')]);
            Route::put('roles/activate/{role}', 'RoleController@activate');
            Route::put('roles/deactivate/{role}', 'RoleController@deactivate');
        });

        Route::group(['namespace' => 'Templates', 'middleware' => 'throttle:60,1'], function () {
            Route::get('templates/designer/{template}', 'QuoteTemplateController@designer');
            Route::apiResource('templates', 'QuoteTemplateController');
            Route::put('templates/activate/{template}', 'QuoteTemplateController@activate');
            Route::put('templates/deactivate/{template}', 'QuoteTemplateController@deactivate');
            Route::put('templates/copy/{template}', 'QuoteTemplateController@copy');

            Route::resource('template_fields', 'TemplateFieldController', ['only' => config('route.crud')]);
            Route::put('template_fields/activate/{template_fields}', 'TemplateFieldController@activate');
            Route::put('template_fields/deactivate/{template_fields}', 'TemplateFieldController@deactivate');
        });

        Route::group(['middleware' => 'throttle:60,1'], function () {
            Route::resource('companies', 'CompanyController', ['only' => config('route.crud')]);
            Route::put('companies/activate/{company}', 'CompanyController@activate');
            Route::put('companies/deactivate/{company}', 'CompanyController@deactivate');
        });

        Route::group([], function () {
            Route::group(['middleware' => 'throttle:60,1'], function () {
                Route::apiResource('vendors', 'VendorController');
                Route::put('vendors/activate/{vendor}', 'VendorController@activate');
                Route::put('vendors/deactivate/{vendor}', 'VendorController@deactivate');
            });
            Route::get('vendors/country/{country}', 'VendorController@country'); // exclusive high throttle rate
        });

        Route::group(['namespace' => 'Margins', 'middleware' => 'throttle:60,1'], function () {
            Route::apiResource('margins', 'CountryMarginController');
            Route::put('margins/activate/{margin}', 'CountryMarginController@activate');
            Route::put('margins/deactivate/{margin}', 'CountryMarginController@deactivate');
        });

        Route::group(['namespace' => 'Discounts', 'prefix' => 'discounts', 'middleware' => 'throttle:60,1'], function () {
            Route::apiResource('multi_year', 'MultiYearDiscountController');
            Route::put('multi_year/activate/{multi_year}', 'MultiYearDiscountController@activate');
            Route::put('multi_year/deactivate/{multi_year}', 'MultiYearDiscountController@deactivate');

            Route::apiResource('pre_pay', 'PrePayDiscountController');
            Route::put('pre_pay/activate/{pre_pay}', 'PrePayDiscountController@activate');
            Route::put('pre_pay/deactivate/{pre_pay}', 'PrePayDiscountController@deactivate');

            Route::apiResource('promotions', 'PromotionalDiscountController');
            Route::put('promotions/activate/{promotion}', 'PromotionalDiscountController@activate');
            Route::put('promotions/deactivate/{promotion}', 'PromotionalDiscountController@deactivate');

            Route::apiResource('snd', 'SNDcontroller');
            Route::put('snd/activate/{snd}', 'SNDcontroller@activate');
            Route::put('snd/deactivate/{snd}', 'SNDcontroller@deactivate');
        });

        Route::group(['prefix' => 'quotes', 'namespace' => 'Quotes'], function () {
            Route::post('handle', 'QuoteFilesController@handle'); // exclusive high throttle rate
            Route::get('/get/{quote}', 'QuoteController@quote'); // exclusive high throttle rate

            Route::group(['middleware' => 'throttle:60,1'], function () {
                Route::get('/discounts/{quote}', 'QuoteController@discounts');
                Route::get('/review/{quote}', 'QuoteController@review');
                Route::post('state', 'QuoteController@storeState');

                /**
                 * User's Drafted Quotes
                 */
                Route::apiResource('drafted', 'QuoteDraftedController', ['only' => config('route.rd')]);
                Route::patch('drafted/{drafted}', 'QuoteDraftedController@activate');
                Route::put('drafted/{drafted}', 'QuoteDraftedController@deactivate');

                /**
                 * User's Submitted Quotes
                 */
                Route::apiResource('submitted', 'QuoteSubmittedController', ['only' => config('route.rd')]);
                Route::patch('submitted/{submitted}', 'QuoteSubmittedController@activate');
                Route::put('submitted/{submitted}', 'QuoteSubmittedController@deactivate');
                Route::put('submitted/copy/{submitted}', 'QuoteSubmittedController@copy');

                Route::apiResource('file', 'QuoteFilesController', ['only' => config('route.cr')]);

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
                });
            });
        });
    });
});

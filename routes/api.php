<?php

Route::group(['namespace' => 'API'], function () {
    Route::group(['prefix' => 'auth', 'middleware' => 'throttle:240,1'], function () {
        Route::post('signin', 'AuthController@signin')->name('signin');
        Route::post('signup', 'AuthController@signup')->name('signup');
        Route::get('signup/{invitation}', 'AuthController@showInvitation');
        Route::post('signup/{invitation}', 'AuthController@signupCollaborator');
        Route::get('reset-password/{reset}', 'AuthController@verifyPasswordReset');
        Route::post('reset-password/{reset}', 'AuthController@resetPassword');

        Route::group(['middleware' => 'auth:api'], function () {
            Route::get('logout', 'AuthController@logout')->name('account.logout');
            Route::match(['get', 'put'], 'user', 'AuthController@user')->name('account.show');
            Route::post('user', 'AuthController@updateOwnProfile')->name('account.update');
        });
    });

    Route::group(['prefix' => 'data', 'namespace' => 'Data'], function () {
        Route::group(['middleware' => 'throttle:240,1'], function () {
            Route::get('timezones', 'TimezonesController');
            Route::get('languages', 'LanguagesController');
            Route::get('currencies', 'CurrenciesController');
            Route::get('fileformats', 'FileFormatsController');
        });
        Route::get('countries', 'CountriesController'); // exclusive high throttle rate
    });

    Route::group(['prefix' => 's4', 'as' => 's4.', 'middleware' => ['throttle:240,1', 'client']], function () {
        Route::get('quotes/{rfq}', 'S4QuoteController@show')->name('quote');
        Route::post('quotes', 'S4QuoteController@store')->name('store');
        Route::get('quotes/{rfq}/price', 'S4QuoteController@price')->name('price');
        Route::get('quotes/{rfq}/schedule', 'S4QuoteController@schedule')->name('schedule');
        Route::get('quotes/{rfq}/pdf', 'S4QuoteController@pdf')->name('pdf');
    });

    Route::group(['middleware' => 'auth:api'], function () {
        Route::group(['middleware' => 'throttle:240,1'], function () {
            Route::apiResource('addresses', 'AddressController');
            Route::put('addresses/activate/{address}', 'AddressController@activate');
            Route::put('addresses/deactivate/{address}', 'AddressController@deactivate');

            Route::apiResource('contacts', 'ContactController');
            Route::put('contacts/activate/{contact}', 'ContactController@activate');
            Route::put('contacts/deactivate/{contact}', 'ContactController@deactivate');
        });

        Route::group(['middleware' => 'throttle:240,1', 'namespace' => 'System'], function () {
            Route::apiResource('settings', 'SystemSettingController', ['only' => config('route.ru')]);
            Route::patch('settings', 'SystemSettingController@updateMany');

            Route::match(['get', 'post'], 'activities', 'ActivityController@index');
            Route::get('activities/meta', 'ActivityController@meta');
            Route::match(['get', 'post'], 'activities/export/{type}', 'ActivityController@export')->where('type', 'csv|excel|pdf');
            Route::match(['get', 'post'], 'activities/subject/{subject}', 'ActivityController@subject');
            Route::match(['get', 'post'], 'activities/subject/{subject}/export/{type}', 'ActivityController@exportSubject');
        });

        Route::group(['middleware' => 'throttle:240,1'], function () {
            Route::get('users/list', 'UserController@list');
            Route::resource('users', 'UserController', ['only' => config('route.crud')]);
            Route::put('users/activate/{user}', 'UserController@activate');
            Route::put('users/deactivate/{user}', 'UserController@deactivate');
            Route::patch('users/reset-password/{user}', 'UserController@resetPassword');
            Route::put('users/reset-account/{user}', 'UserController@resetAccount');

            Route::apiResource('invitations', 'InvitationController', ['only' => config('route.rd')]);
            Route::put('invitations/resend/{invitation}', 'InvitationController@resend');
            Route::put('invitations/cancel/{invitation}', 'InvitationController@cancel');
        });

        Route::group(['middleware' => 'throttle:240,1'], function () {
            Route::resource('roles', 'RoleController', ['only' => config('route.crud')]);
            Route::put('roles/activate/{role}', 'RoleController@activate');
            Route::put('roles/deactivate/{role}', 'RoleController@deactivate');
        });

        Route::group(['namespace' => 'Templates', 'middleware' => 'throttle:240,1'], function () {
            Route::get('templates/designer/{template}', 'QuoteTemplateController@designer');
            Route::apiResource('templates', 'QuoteTemplateController');
            Route::put('templates/activate/{template}', 'QuoteTemplateController@activate');
            Route::put('templates/deactivate/{template}', 'QuoteTemplateController@deactivate');
            Route::put('templates/copy/{template}', 'QuoteTemplateController@copy');

            Route::resource('template_fields', 'TemplateFieldController', ['only' => config('route.crud')]);
            Route::put('template_fields/activate/{template_fields}', 'TemplateFieldController@activate');
            Route::put('template_fields/deactivate/{template_fields}', 'TemplateFieldController@deactivate');
        });

        Route::group(['middleware' => 'throttle:240,1'], function () {
            Route::resource('companies', 'CompanyController', ['only' => config('route.crud')]);
            Route::put('companies/activate/{company}', 'CompanyController@activate');
            Route::put('companies/deactivate/{company}', 'CompanyController@deactivate');
        });

        Route::group([], function () {
            Route::group(['middleware' => 'throttle:240,1'], function () {
                Route::apiResource('vendors', 'VendorController');
                Route::put('vendors/activate/{vendor}', 'VendorController@activate');
                Route::put('vendors/deactivate/{vendor}', 'VendorController@deactivate');
            });
            Route::get('vendors/country/{country}', 'VendorController@country'); // exclusive high throttle rate
        });

        Route::group(['namespace' => 'Margins', 'middleware' => 'throttle:240,1'], function () {
            Route::apiResource('margins', 'CountryMarginController');
            Route::put('margins/activate/{margin}', 'CountryMarginController@activate');
            Route::put('margins/deactivate/{margin}', 'CountryMarginController@deactivate');
        });

        Route::group(['namespace' => 'Discounts', 'prefix' => 'discounts', 'middleware' => 'throttle:240,1'], function () {
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
            Route::get('/groups/{quote}', 'QuoteController@rowsGroups'); // exclusive high throttle rate
            Route::get('/groups/{quote}/{group}', 'QuoteController@showGroupDescription'); // exclusive high throttle rate
            Route::post('/groups/{quote}', 'QuoteController@storeGroupDescription'); // exclusive high throttle rate
            Route::patch('/groups/{quote}/{group}', 'QuoteController@updateGroupDescription'); // exclusive high throttle rate
            Route::put('/groups/{quote}', 'QuoteController@moveGroupDescriptionRows'); // exclusive high throttle rate
            Route::delete('/groups/{quote}/{group}', 'QuoteController@destroyGroupDescription'); // exclusive high throttle rate

            Route::group(['middleware' => 'throttle:240,1'], function () {
                Route::get('/discounts/{quote}', 'QuoteController@discounts');
                Route::post('/try-discounts/{quote}', 'QuoteController@tryDiscounts');
                Route::get('/review/{quote}', 'QuoteController@review');
                Route::post('state', 'QuoteController@storeState');
                Route::patch('version/{quote}', 'QuoteController@setVersion');

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
                Route::put('submitted/unsubmit/{submitted}', 'QuoteSubmittedController@unsubmit');

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

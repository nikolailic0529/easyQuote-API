<?php

use App\Http\Controllers\API\System\SystemSettingController;

Route::group(['namespace' => 'API'], function () {
    Route::get('settings/public', [SystemSettingController::class, 'showPublicSettings']);

    Route::group(['prefix' => 'auth', 'middleware' => THROTTLE_RATE_01], function () {
        Route::get('attempts/{email}', 'AuthController@showAttempts');
        
        Route::post('signin', 'AuthController@signin')->name('signin');
        Route::post('signup', 'AuthController@signup')->name('signup');
        Route::post('logout-user', 'AuthController@authenticateAndLogout');

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

    /** Maintenance. */
    Route::group(['namespace' => 'System'], function () {
        Route::get('maintenance', 'MaintenanceController@show');
        Route::group(['middleware' => ['auth:api', 'role:Administrator']], function () {
            Route::post('maintenance', 'MaintenanceController@start');
            Route::put('maintenance', 'MaintenanceController@stop');
        });
    });

    Route::group(['prefix' => 'data', 'namespace' => 'Data'], function () {
        Route::group(['middleware' => THROTTLE_RATE_01], function () {
            Route::get('timezones', 'TimezonesController');
            Route::get('languages', 'LanguagesController');
            Route::get('currencies', 'CurrencyController');
            Route::get('currencies/xr', 'CurrencyController@showAllHavingExrate');
            Route::get('fileformats', 'FileFormatsController');
        });
        Route::get('countries', 'CountryController'); // exclusive high throttle rate
    });

    Route::group(['prefix' => 's4', 'as' => 's4.', 'middleware' => [THROTTLE_RATE_01]], function () {
        Route::get('quotes/{rfq}', 'S4QuoteController@show')->name('quote');
        Route::post('quotes', 'S4QuoteController@store')->name('store');
        Route::get('quotes/{rfq}/price', 'S4QuoteController@price')->name('price');
        Route::get('quotes/{rfq}/schedule', 'S4QuoteController@schedule')->name('schedule');
        Route::get('quotes/{rfq}/pdf', 'S4QuoteController@pdf')->name('pdf');
    });

Route::group(['middleware' => 'auth:api'], function () {
        Route::group(['middleware' => THROTTLE_RATE_01], function () {
            Route::match(['get', 'post'], 'stats', 'StatsController@quotesSummary');
            Route::match(['get', 'post'], 'stats/customers', 'StatsController@customersSummary');
            Route::post('stats/customers/map', 'StatsController@mapCustomers');
            Route::post('stats/assets/map', 'StatsController@mapAssets');
            Route::post('stats/quotes/map', 'StatsController@mapQuotes');
            Route::get('stats/locations/{location}/quotes', 'StatsController@quotesByLocation');

            Route::post('attachments', 'AttachmentController');

            Route::resource('assets', 'AssetController')->only(ROUTE_CRUD);
            Route::post('assets/unique', 'AssetController@checkUniqueness');
            Route::post('lookup/service', 'ServiceController');
        });

        Route::group(['middleware' => THROTTLE_RATE_01, 'namespace' => 'Data'], function () {
            Route::get('countries/vendor/{vendor}', 'CountryController@filterCountriesByVendor');
            Route::get('countries/company/{company}', 'CountryController@filterCountriesByCompany');
            Route::apiResource('countries', 'CountryController');
            Route::put('countries/activate/{country}', 'CountryController@activate');
            Route::put('countries/deactivate/{country}', 'CountryController@deactivate');

            Route::post('currencies/rate', 'CurrencyController@targetRate');
        });

        Route::group(['middleware' => THROTTLE_RATE_01], function () {
            Route::apiResource('addresses', 'AddressController');
            Route::put('addresses/activate/{address}', 'AddressController@activate');
            Route::put('addresses/deactivate/{address}', 'AddressController@deactivate');

            Route::apiResource('contacts', 'ContactController');
            Route::put('contacts/activate/{contact}', 'ContactController@activate');
            Route::put('contacts/deactivate/{contact}', 'ContactController@deactivate');
        });

        Route::group(['middleware' => THROTTLE_RATE_01, 'namespace' => 'System'], function () {
            Route::group(['middleware' => THROTTLE_RATE_01], function () {
                Route::apiResource('notifications', 'NotificationController', ['only' => ['index', 'destroy']]);
                Route::get('notifications/latest', 'NotificationController@latest');
                Route::delete('notifications', 'NotificationController@destroyAll');
                Route::put('notifications/{notification}', 'NotificationController@read');
                Route::put('notifications', 'NotificationController@readAll');
            });

            Route::apiResource('settings', 'SystemSettingController', ['only' => ROUTE_RU]);
            Route::patch('settings', 'SystemSettingController@updateMany');

            Route::match(['get', 'post'], 'activities', 'ActivityController@index');
            Route::get('activities/meta', 'ActivityController@meta');
            Route::match(['get', 'post'], 'activities/export/{type}', 'ActivityController@export')->where('type', 'csv|excel|pdf');
            Route::match(['get', 'post'], 'activities/subject/{subject}', 'ActivityController@subject');
            Route::match(['get', 'post'], 'activities/subject/{subject}/export/{type}', 'ActivityController@exportSubject');

            Route::apiResource('importable-columns', 'ImportableColumnController');
            Route::put('importable-columns/activate/{importable_column}', 'ImportableColumnController@activate');
            Route::put('importable-columns/deactivate/{importable_column}', 'ImportableColumnController@deactivate');
        });

        Route::group(['middleware' => THROTTLE_RATE_01], function () {
            Route::get('users/list', 'UserController@list');
            Route::get('users/exlist', 'UserController@exclusiveList');
            Route::post('users/roles', 'UserController@listByRoles');
            Route::resource('users', 'UserController', ['only' => ROUTE_CRUD]);
            Route::put('users/activate/{user}', 'UserController@activate');
            Route::put('users/deactivate/{user}', 'UserController@deactivate');
            Route::patch('users/reset-password/{user}', 'UserController@resetPassword');
            Route::put('users/reset-account/{user}', 'UserController@resetAccount');

            Route::apiResource('invitations', 'InvitationController', ['only' => ROUTE_RD]);
            Route::put('invitations/resend/{invitation}', 'InvitationController@resend');
            Route::put('invitations/cancel/{invitation}', 'InvitationController@cancel');

            Route::resource('roles', 'RoleController', ['only' => ROUTE_CRUD]);
            Route::put('roles/activate/{role}', 'RoleController@activate');
            Route::put('roles/deactivate/{role}', 'RoleController@deactivate');
            Route::get('roles/module/{module}', 'RoleController@module');

            Route::put('permissions/module', 'PermissionController@grantModulePermission');
            Route::get('permissions/module/{module}', 'PermissionController@showModulePermissionForm');
        });

        Route::group(['namespace' => 'Templates', 'middleware' => THROTTLE_RATE_01], function () {
            Route::get('templates/designer/{template}', 'QuoteTemplateController@designer');
            Route::get('templates/country/{country}', 'QuoteTemplateController@country');
            Route::apiResource('templates', 'QuoteTemplateController');
            Route::put('templates/activate/{template}', 'QuoteTemplateController@activate');
            Route::put('templates/deactivate/{template}', 'QuoteTemplateController@deactivate');
            Route::put('templates/copy/{template}', 'QuoteTemplateController@copy');
        });

        Route::group(['namespace' => 'Templates', 'middleware' => THROTTLE_RATE_01], function () {
            Route::get('contract-templates/designer/{contract_template}', 'ContractTemplateController@designer');
            Route::get('contract-templates/country/{country}', 'ContractTemplateController@country');
            Route::apiResource('contract-templates', 'ContractTemplateController');
            Route::put('contract-templates/activate/{contract_template}', 'ContractTemplateController@activate');
            Route::put('contract-templates/deactivate/{contract_template}', 'ContractTemplateController@deactivate');
            Route::put('contract-templates/copy/{contract_template}', 'ContractTemplateController@copy');
        });
        
        Route::group(['namespace' => 'Templates', 'middleware' => THROTTLE_RATE_01], function () {
            Route::get('hpe-contract-templates/designer/{hpe_contract_template}', 'HpeContractTemplateController@designer');
            Route::get('hpe-contract-templates/country/{country}', 'HpeContractTemplateController@country');
            Route::post('hpe-contract-templates/filter', 'HpeContractTemplateController@filterTemplates');
            Route::apiResource('hpe-contract-templates', 'HpeContractTemplateController');
            Route::put('hpe-contract-templates/activate/{hpe_contract_template}', 'HpeContractTemplateController@activate');
            Route::put('hpe-contract-templates/deactivate/{hpe_contract_template}', 'HpeContractTemplateController@deactivate');
            Route::put('hpe-contract-templates/copy/{hpe_contract_template}', 'HpeContractTemplateController@copy');
        });

        Route::group(['middleware' => THROTTLE_RATE_01], function () {
            Route::get('companies/external', 'CompanyController@getExternal');
            Route::get('companies/internal', 'CompanyController@getInternal');
            Route::get('companies/countries', 'CompanyController@showCompaniesWithCountries');
            
            Route::resource('companies', 'CompanyController', ['only' => ROUTE_CRUD]);

            Route::put('companies/activate/{company}', 'CompanyController@activate');
            Route::put('companies/deactivate/{company}', 'CompanyController@deactivate');
        });

        Route::group([], function () {
            Route::group(['middleware' => THROTTLE_RATE_01], function () {
                Route::apiResource('vendors', 'VendorController');
                Route::put('vendors/activate/{vendor}', 'VendorController@activate');
                Route::put('vendors/deactivate/{vendor}', 'VendorController@deactivate');
            });
            Route::get('vendors/country/{country}', 'VendorController@country'); // exclusive high throttle rate
        });

        Route::group(['namespace' => 'Margins', 'middleware' => THROTTLE_RATE_01], function () {
            Route::apiResource('margins', 'CountryMarginController');
            Route::put('margins/activate/{margin}', 'CountryMarginController@activate');
            Route::put('margins/deactivate/{margin}', 'CountryMarginController@deactivate');
        });

        Route::group(['namespace' => 'Discounts', 'prefix' => 'discounts', 'middleware' => THROTTLE_RATE_01], function () {
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

        Route::post('hpe-contract-files', 'HpeContractFileController');

        Route::get('hpe-contracts/step/import', 'HpeContractController@showImportStepData');
        Route::patch('hpe-contracts/{hpe_contract}/import/{hpe_contract_file}', 'HpeContractController@importHpeContract');
        Route::get('hpe-contracts/{hpe_contract}/review', 'HpeContractController@reviewHpeContractData');
        Route::get('hpe-contracts/{hpe_contract}/preview', 'HpeContractController@previewHpeContract');
        Route::patch('hpe-contracts/{hpe_contract}/select-assets', 'HpeContractController@selectAssets');

        Route::put('hpe-contracts/{hpe_contract}/copy', 'HpeContractController@copyHpeContract');
        Route::patch('hpe-contracts/{hpe_contract}/submit', 'HpeContractController@submitHpeContract');
        Route::patch('hpe-contracts/{hpe_contract}/unsubmit', 'HpeContractController@unsubmitHpeContract');
        Route::patch('hpe-contracts/{hpe_contract}/activate', 'HpeContractController@activateHpeContract');
        Route::patch('hpe-contracts/{hpe_contract}/deactivate', 'HpeContractController@deactivateHpeContract');
        Route::get('hpe-contracts/{hpe_contract}/export', 'HpeContractController@exportHpeContract');
        Route::apiResource('hpe-contracts', 'HpeContractController');
        

        Route::group(['prefix' => 'contracts', 'namespace' => 'Contracts', 'as' => 'contracts.'], function () {
            /**
             * Contract State.
             */
            Route::apiResource('state', 'ContractStateController')->only(['show', 'update'])->parameters([
                'state' => 'contract'
            ]);
            Route::get('state/review/{contract}', 'ContractStateController@review');

            /**
             * Drafted Contracts.
             */
            Route::apiResource('drafted', 'ContractDraftedController', ['only' => ROUTE_RD]);
            Route::patch('drafted/{drafted}', 'ContractDraftedController@activate');
            Route::put('drafted/{drafted}', 'ContractDraftedController@deactivate');
            Route::post('drafted/submit/{drafted}', 'ContractDraftedController@submit');

            /**
             * Submitted Contracts.
             */
            Route::apiResource('submitted', 'ContractSubmittedController', ['only' => ROUTE_RD]);
            Route::patch('submitted/{submitted}', 'ContractSubmittedController@activate');
            Route::put('submitted/{submitted}', 'ContractSubmittedController@deactivate');
            Route::post('submitted/unsubmit/{submitted}', 'ContractSubmittedController@unsubmit');
        });

        Route::group(['prefix' => 'quotes', 'namespace' => 'Quotes', 'as' => 'quotes.'], function () {
            Route::post('handle', 'QuoteFilesController@handle'); // exclusive high throttle rate
            Route::put('/get/{quote}', 'QuoteController@quote'); // exclusive high throttle rate
            Route::get('/get/{quote}/quote-files/{file_type}', 'QuoteController@downloadQuoteFile')->where('file_type', 'price|schedule'); // exclusive high throttle rate
            Route::get('/groups/{quote}', 'QuoteController@rowsGroups'); // exclusive high throttle rate
            Route::get('/groups/{quote}/{group}', 'QuoteController@showGroupDescription'); // exclusive high throttle rate
            Route::post('/groups/{quote}', 'QuoteController@storeGroupDescription'); // exclusive high throttle rate
            Route::patch('/groups/{quote}/{group}', 'QuoteController@updateGroupDescription'); // exclusive high throttle rate
            Route::put('/groups/{quote}', 'QuoteController@moveGroupDescriptionRows'); // exclusive high throttle rate
            Route::delete('/groups/{quote}/{group}', 'QuoteController@destroyGroupDescription'); // exclusive high throttle rate
            Route::put('/groups/{quote}/select', 'QuoteController@selectGroupDescription');

            Route::get('permissions/{quote}', 'QuoteController@showAuthorizedQuoteUsers');
            Route::put('permissions/{quote}', 'QuoteController@givePermissionToQuote');

            Route::get('notes/{quote}', 'QuoteNoteController@index');
            Route::get('notes/{quote}/{quote_note}', 'QuoteNoteController@show');
            Route::post('notes/{quote}', 'QuoteNoteController@store');
            Route::patch('notes/{quote}/{quote_note}', 'QuoteNoteController@update');
            Route::delete('notes/{quote}/{quote_note}', 'QuoteNoteController@destroy');

            Route::get('tasks/create', 'QuoteTaskController@create');
            Route::put('tasks/template', 'QuoteTaskController@updateTemplate');
            Route::patch('tasks/template', 'QuoteTaskController@resetTemplate');
            Route::get('tasks/{quote}', 'QuoteTaskController@index');
            Route::get('tasks/{quote}/{task}', 'QuoteTaskController@show');
            Route::post('tasks/{quote}', 'QuoteTaskController@store');
            Route::patch('tasks/{quote}/{task}', 'QuoteTaskController@update');
            Route::delete('tasks/{quote}/{task}', 'QuoteTaskController@destroy');

            Route::group(['middleware' => THROTTLE_RATE_01], function () {
                Route::get('/discounts/{quote}', 'QuoteController@discounts');
                Route::post('/try-discounts/{quote}', 'QuoteController@tryDiscounts');
                Route::get('/review/{quote}', 'QuoteController@review');
                Route::post('state', 'QuoteController@storeState');
                Route::patch('version/{quote}', 'QuoteController@setVersion');

                /**
                 * Drafted Quotes
                 */
                Route::apiResource('drafted', 'QuoteDraftedController', ['only' => ROUTE_RD]);
                Route::patch('drafted/{drafted}', 'QuoteDraftedController@activate');
                Route::put('drafted/{drafted}', 'QuoteDraftedController@deactivate');
                Route::delete('drafted/version/{version}', 'QuoteDraftedController@destroyVersion');

                /**
                 * Submitted Quotes
                 */
                Route::get('submitted/pdf/{submitted}', 'QuoteSubmittedController@pdf');
                Route::get('submitted/pdf/{submitted}/contract', 'QuoteSubmittedController@contractPdf');
                Route::apiResource('submitted', 'QuoteSubmittedController', ['only' => ROUTE_RD]);
                Route::patch('submitted/{submitted}', 'QuoteSubmittedController@activate');
                Route::put('submitted/{submitted}', 'QuoteSubmittedController@deactivate');
                Route::put('submitted/copy/{submitted}', 'QuoteSubmittedController@copy');
                Route::put('submitted/unsubmit/{submitted}', 'QuoteSubmittedController@unsubmit');
                Route::post('submitted/contract/{submitted}', 'QuoteSubmittedController@createContract');
                Route::put('submitted/contract-template/{submitted}/{template}', 'QuoteSubmittedController@setContractTemplate');

                Route::apiResource('file', 'QuoteFilesController', ['only' => ROUTE_CR]);

                /**
                 * Customers
                 */
                Route::apiResource('customers', 'CustomerController', ['only' => ROUTE_CRD]);
                Route::patch('customers/{eq_customer}', 'CustomerController@update');

                Route::get('customers/number/{company}/{customer?}', 'CustomerController@giveCustomerNumber');

                Route::group(['prefix' => 'step'], function () {
                    Route::get('1', 'QuoteController@step1');
                    Route::post('1', 'QuoteController@templates');
                    Route::post('2', 'QuoteController@step2');
                    Route::get('3', 'QuoteController@step3');
                });
            });
        });
    });
});

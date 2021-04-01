<?php

use App\Http\Controllers\API\AddressController;
use App\Http\Controllers\API\AssetController;
use App\Http\Controllers\API\AttachmentController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\BusinessDivisionController;
use App\Http\Controllers\API\CompanyController;
use App\Http\Controllers\API\ContactController;
use App\Http\Controllers\API\Contracts\ContractDraftedController;
use App\Http\Controllers\API\Contracts\ContractStateController;
use App\Http\Controllers\API\Contracts\ContractSubmittedController;
use App\Http\Controllers\API\ContractTypeController;
use App\Http\Controllers\API\Data\CountryController;
use App\Http\Controllers\API\Data\CurrencyController;
use App\Http\Controllers\API\Data\FileFormatsController;
use App\Http\Controllers\API\Data\LanguagesController;
use App\Http\Controllers\API\Data\TimezonesController;
use App\Http\Controllers\API\Discounts\MultiYearDiscountController;
use App\Http\Controllers\API\Discounts\PrePayDiscountController;
use App\Http\Controllers\API\Discounts\PromotionalDiscountController;
use App\Http\Controllers\API\Discounts\SNDcontroller;
use App\Http\Controllers\API\HpeContractController;
use App\Http\Controllers\API\HpeContractFileController;
use App\Http\Controllers\API\InvitationController;
use App\Http\Controllers\API\Margins\CountryMarginController;
use App\Http\Controllers\API\OpportunityController;
use App\Http\Controllers\API\PermissionController;
use App\Http\Controllers\API\Quotes\CustomerController;
use App\Http\Controllers\API\Quotes\QuoteController;
use App\Http\Controllers\API\Quotes\QuoteDraftedController;
use App\Http\Controllers\API\Quotes\QuoteFilesController;
use App\Http\Controllers\API\Quotes\QuoteNoteController;
use App\Http\Controllers\API\Quotes\QuoteSubmittedController;
use App\Http\Controllers\API\Quotes\QuoteTaskController;
use App\Http\Controllers\API\RoleController;
use App\Http\Controllers\API\S4QuoteController;
use App\Http\Controllers\API\SalesOrders\SalesOrderController;
use App\Http\Controllers\API\SalesOrders\SalesOrderDraftedController;
use App\Http\Controllers\API\SalesOrders\SalesOrderSubmittedController;
use App\Http\Controllers\API\ServiceController;
use App\Http\Controllers\API\StatsController;
use App\Http\Controllers\API\System\ActivityController;
use App\Http\Controllers\API\System\CustomFieldController;
use App\Http\Controllers\API\System\ImportableColumnController;
use App\Http\Controllers\API\System\MaintenanceController;
use App\Http\Controllers\API\System\NotificationController;
use App\Http\Controllers\API\System\SystemSettingController;
use App\Http\Controllers\API\TeamController;
use App\Http\Controllers\API\Templates\ContractTemplateController;
use App\Http\Controllers\API\Templates\HpeContractTemplateController;
use App\Http\Controllers\API\Templates\OpportunityTemplateController;
use App\Http\Controllers\API\Templates\QuoteTemplateController;
use App\Http\Controllers\API\UnifiedQuoteController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VendorController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideCustomerController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideDistributionController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideQuoteAssetController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideQuoteController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideQuoteDraftedController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideQuoteNoteController;
use App\Http\Controllers\API\WorldwideQuotes\WorldwideQuoteSubmittedController;
use Illuminate\Support\Facades\Route;

Route::get('settings/public', [SystemSettingController::class, 'showPublicSettings']);

Route::group(['prefix' => 'auth', 'middleware' => THROTTLE_RATE_01], function () {
    Route::get('attempts/{email}', [AuthController::class, 'showAttempts']);

    Route::post('signin', [AuthController::class, 'signin'])->name('signin');
    Route::post('logout-user', [AuthController::class, 'authenticateAndLogout']);

    Route::get('signup/{invitation}', [AuthController::class, 'showInvitation']);
    Route::post('signup/{invitation}', [AuthController::class, 'signupCollaborator']);

    Route::get('reset-password/{reset}', [AuthController::class, 'verifyPasswordReset']);
    Route::post('reset-password/{reset}', [AuthController::class, 'resetPassword']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('logout', [AuthController::class, 'logout'])->name('account.logout');
        Route::match(['get', 'put'], 'user', [AuthController::class, 'user'])->name('account.show');
        Route::post('user', [AuthController::class, 'updateOwnProfile'])->name('account.update');
    });
});

/** Maintenance. */
Route::get('maintenance', [MaintenanceController::class, 'show']);
Route::group(['middleware' => ['auth:api', 'role:Administrator']], function () {
    Route::post('maintenance', [MaintenanceController::class, 'start']);
    Route::put('maintenance', [MaintenanceController::class, 'stop']);
});

Route::group(['prefix' => 'data'], function () {
    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('timezones', TimezonesController::class);
        Route::get('languages', LanguagesController::class);
        Route::get('currencies', CurrencyController::class);
        Route::get('currencies/xr', [CurrencyController::class, 'showAllHavingExrate']);
        Route::get('fileformats', FileFormatsController::class);
    });
    Route::get('countries', CountryController::class);
});

Route::group(['prefix' => 's4', 'as' => 's4.', 'middleware' => [THROTTLE_RATE_01]], function () {
    Route::get('quotes/{rfq}', [S4QuoteController::class, 'show'])->name('quote');
    Route::post('quotes', [S4QuoteController::class, 'store'])->name('store');
    Route::get('quotes/{rfq}/price', [S4QuoteController::class, 'price'])->name('price');
    Route::get('quotes/{rfq}/schedule', [S4QuoteController::class, 'schedule'])->name('schedule');
    Route::get('quotes/{rfq}/pdf', [S4QuoteController::class, 'pdf'])->name('pdf');
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('business-divisions', BusinessDivisionController::class);
    Route::get('contract-types', ContractTypeController::class);

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::match(['get', 'post'], 'stats', [StatsController::class, 'quotesSummary']);
        Route::match(['get', 'post'], 'stats/customers', [StatsController::class, 'customersSummary']);
        Route::post('stats/customers/map', [StatsController::class, 'mapCustomers']);
        Route::post('stats/assets/map', [StatsController::class, 'mapAssets']);
        Route::post('stats/quotes/map', [StatsController::class, 'mapQuotes']);
        Route::get('stats/locations/{location}/quotes', [StatsController::class, 'quotesByLocation']);

        Route::post('attachments', AttachmentController::class);

        Route::resource('assets', AssetController::class)->only(ROUTE_CRUD);
        Route::post('assets/unique', [AssetController::class, 'checkUniqueness']);
        Route::post('lookup/service', ServiceController::class);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('countries/vendor/{vendor}', [CountryController::class, 'filterCountriesByVendor']);
        Route::get('countries/company/{company}', [CountryController::class, 'filterCountriesByCompany']);
        Route::apiResource('countries', CountryController::class);
        Route::put('countries/activate/{country}', [CountryController::class, 'activate']);
        Route::put('countries/deactivate/{country}', [CountryController::class, 'deactivate']);

        Route::post('currencies/rate', [CurrencyController::class, 'targetRate']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::apiResource('addresses', AddressController::class);
        Route::put('addresses/activate/{address}', [AddressController::class, 'activate']);
        Route::put('addresses/deactivate/{address}', [AddressController::class, 'deactivate']);

        Route::apiResource('contacts', ContactController::class);
        Route::put('contacts/activate/{contact}', [ContactController::class, 'activate']);
        Route::put('contacts/deactivate/{contact}', [ContactController::class, 'deactivate']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::apiResource('notifications', NotificationController::class, ['only' => ['index', 'destroy']]);
        Route::get('notifications/latest', [NotificationController::class, 'latest']);
        Route::delete('notifications', [NotificationController::class, 'destroyAll']);
        Route::put('notifications/{notification}', [NotificationController::class, 'read']);
        Route::put('notifications', [NotificationController::class, 'readAll']);

        Route::apiResource('settings', SystemSettingController::class, ['only' => ROUTE_RU]);
        Route::patch('settings', [SystemSettingController::class, 'updateMany']);

        Route::match(['get', 'post'], 'activities', [ActivityController::class, 'index']);
        Route::get('activities/meta', [ActivityController::class, 'meta']);
        Route::match(['get', 'post'], 'activities/export/{type}', [ActivityController::class, 'export'])->where('type', 'csv|excel|pdf');
        Route::match(['get', 'post'], 'activities/subject/{subject}', [ActivityController::class, 'subject']);
        Route::match(['get', 'post'], 'activities/subject/{subject}/export/{type}', [ActivityController::class, 'exportSubject']);

        Route::apiResource('importable-columns', ImportableColumnController::class);
        Route::put('importable-columns/activate/{importable_column}', [ImportableColumnController::class, 'activate']);
        Route::put('importable-columns/deactivate/{importable_column}', [ImportableColumnController::class, 'deactivate']);

        Route::get('custom-fields', [CustomFieldController::class, 'showListOfCustomFields']);
        Route::get('custom-field-values/{custom_field_name}', [CustomFieldController::class, 'showValuesOfCustomFieldByFieldName']);
        Route::put('custom-field-values/{custom_field_name}', [CustomFieldController::class, 'updateValuesOfCustomField']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('users/list', [UserController::class, 'list']);
        Route::get('users/exlist', [UserController::class, 'exclusiveList']);
        Route::post('users/roles', [UserController::class, 'listByRoles']);
        Route::get('users', [UserController::class, 'paginateUsers']);
        Route::get('users/create', [UserController::class, 'showUserFormData']);
        Route::get('users/{user}', [UserController::class, 'showUser']);
        Route::post('users', [UserController::class, 'inviteUser']);
        Route::patch('users/{user}', [UserController::class, 'updateUser']);
        Route::delete('users/{user}', [UserController::class, 'destroyUser']);
        Route::put('users/activate/{user}', [UserController::class, 'activate']);
        Route::put('users/deactivate/{user}', [UserController::class, 'deactivate']);
        Route::patch('users/reset-password/{user}', [UserController::class, 'resetPassword']);
        Route::put('users/reset-account/{user}', [UserController::class, 'resetAccount']);

        Route::apiResource('invitations', InvitationController::class, ['only' => ROUTE_RD]);
        Route::put('invitations/resend/{invitation}', [InvitationController::class, 'resend']);
        Route::put('invitations/cancel/{invitation}', [InvitationController::class, 'cancel']);

        Route::resource('roles', RoleController::class, ['only' => ROUTE_CRUD]);
        Route::put('roles/activate/{role}', [RoleController::class, 'activate']);
        Route::put('roles/deactivate/{role}', [RoleController::class, 'deactivate']);
        Route::get('roles/module/{module}', [RoleController::class, 'module']);

        Route::put('permissions/module', [PermissionController::class, 'grantModulePermission']);
        Route::get('permissions/module/{module}', [PermissionController::class, 'showModulePermissionForm']);

        /**
         * Teams.
         */
        Route::get('teams', [TeamController::class, 'paginateTeams']);
        Route::get('teams/list', [TeamController::class, 'showListOfTeams']);
        Route::get('teams/{team}', [TeamController::class, 'showTeam']);
        Route::post('teams', [TeamController::class, 'storeTeam']);
        Route::patch('teams/{team}', [TeamController::class, 'updateTeam']);
        Route::delete('teams/{team}', [TeamController::class, 'deleteTeam']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('templates/designer/{template}', [QuoteTemplateController::class, 'showTemplateSchema']);
        Route::get('templates/country/{country}', [QuoteTemplateController::class, 'filterTemplatesByCountry']);
        Route::apiResource('templates', QuoteTemplateController::class);
        Route::put('templates/activate/{template}', [QuoteTemplateController::class, 'activate']);
        Route::put('templates/deactivate/{template}', [QuoteTemplateController::class, 'deactivate']);
        Route::put('templates/copy/{template}', [QuoteTemplateController::class, 'copy']);

        Route::post('templates/filter', [QuoteTemplateController::class, 'filterRescueTemplates']);
        Route::post('templates/filter-ww', [QuoteTemplateController::class, 'filterWorldwideTemplates']);
        Route::post('templates/filter-ww/pack', [QuoteTemplateController::class, 'filterWorldwidePackTemplates']);
        Route::post('templates/filter-ww/contract', [QuoteTemplateController::class, 'filterWorldwideContractTemplates']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('contract-templates/designer/{contract_template}', [ContractTemplateController::class, 'designer']);
        Route::get('contract-templates/country/{country}', [ContractTemplateController::class, 'country']);
        Route::apiResource('contract-templates', ContractTemplateController::class);
        Route::put('contract-templates/activate/{contract_template}', [ContractTemplateController::class, 'activate']);
        Route::put('contract-templates/deactivate/{contract_template}', [ContractTemplateController::class, 'deactivate']);
        Route::put('contract-templates/copy/{contract_template}', [ContractTemplateController::class, 'copy']);

        Route::post('contract-templates/filter-ww/pack', [ContractTemplateController::class, 'filterWorldwidePackContractTemplates']);
        Route::post('contract-templates/filter-ww/contract', [ContractTemplateController::class, 'filterWorldwideContractContractTemplates']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('hpe-contract-templates/designer/{hpe_contract_template}', [HpeContractTemplateController::class, 'showTemplateSchema']);
        Route::get('hpe-contract-templates/country/{country}', [HpeContractTemplateController::class, 'filterTemplatesByCountry']);
        Route::post('hpe-contract-templates/filter', [HpeContractTemplateController::class, 'filterTemplates']);
        Route::get('hpe-contract-templates', [HpeContractTemplateController::class, 'paginateTemplates']);
        Route::get('hpe-contract-templates/{hpe_contract_template}', [HpeContractTemplateController::class, 'showTemplate']);
        Route::post('hpe-contract-templates', [HpeContractTemplateController::class, 'storeTemplate']);
        Route::patch('hpe-contract-templates/{hpe_contract_template}', [HpeContractTemplateController::class, 'updateTemplate']);
        Route::delete('hpe-contract-templates/{hpe_contract_template}', [HpeContractTemplateController::class, 'destroyTemplate']);
        Route::put('hpe-contract-templates/activate/{hpe_contract_template}', [HpeContractTemplateController::class, 'activateTemplate']);
        Route::put('hpe-contract-templates/deactivate/{hpe_contract_template}', [HpeContractTemplateController::class, 'deactivateTemplate']);
        Route::put('hpe-contract-templates/copy/{hpe_contract_template}', [HpeContractTemplateController::class, 'replicateTemplate']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('companies/external', [CompanyController::class, 'getExternal']);
        Route::get('companies/internal', [CompanyController::class, 'getInternal']);
        Route::get('companies/countries', [CompanyController::class, 'showCompaniesWithCountries']);
        Route::get('external-companies', [CompanyController::class, 'paginateExternalCompanies']);

        Route::resource('companies', CompanyController::class, ['only' => ROUTE_CRUD]);

        Route::put('companies/activate/{company}', [CompanyController::class, 'activate']);
        Route::put('companies/deactivate/{company}', [CompanyController::class, 'deactivate']);

        Route::patch('companies/{company}/contacts/{contact:id}', [CompanyController::class, 'updateCompanyContact']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('vendors/list', [VendorController::class, 'showVendorsList']);
        Route::apiResource('vendors', VendorController::class);
        Route::put('vendors/activate/{vendor}', [VendorController::class, 'activate']);
        Route::put('vendors/deactivate/{vendor}', [VendorController::class, 'deactivate']);
    });
    Route::get('vendors/country/{country}', [VendorController::class, 'country']);

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::apiResource('margins', CountryMarginController::class);
        Route::put('margins/activate/{margin}', [CountryMarginController::class, 'activate']);
        Route::put('margins/deactivate/{margin}', [CountryMarginController::class, 'deactivate']);
    });

    Route::group(['prefix' => 'discounts', 'middleware' => THROTTLE_RATE_01], function () {
        Route::apiResource('multi_year', MultiYearDiscountController::class);
        Route::put('multi_year/activate/{multi_year}', [MultiYearDiscountController::class, 'activate']);
        Route::put('multi_year/deactivate/{multi_year}', [MultiYearDiscountController::class, 'deactivate']);

        Route::apiResource('pre_pay', PrePayDiscountController::class);
        Route::put('pre_pay/activate/{pre_pay}', [PrePayDiscountController::class, 'activate']);
        Route::put('pre_pay/deactivate/{pre_pay}', [PrePayDiscountController::class, 'deactivate']);

        Route::apiResource('promotions', PromotionalDiscountController::class);
        Route::put('promotions/activate/{promotion}', [PromotionalDiscountController::class, 'activate']);
        Route::put('promotions/deactivate/{promotion}', [PromotionalDiscountController::class, 'deactivate']);

        Route::apiResource('snd', SNDcontroller::class);
        Route::put('snd/activate/{snd}', [SNDcontroller::class, 'activate']);
        Route::put('snd/deactivate/{snd}', [SNDcontroller::class, 'deactivate']);
    });

    Route::post('hpe-contract-files', HpeContractFileController::class);

    Route::get('hpe-contracts/step/import', [HpeContractController::class, 'showImportStepData']);
    Route::patch('hpe-contracts/{hpe_contract}/import/{hpe_contract_file}', [HpeContractController::class, 'importHpeContract']);
    Route::get('hpe-contracts/{hpe_contract}/review', [HpeContractController::class, 'reviewHpeContractData']);
    Route::get('hpe-contracts/{hpe_contract}/preview', [HpeContractController::class, 'previewHpeContract']);
    Route::patch('hpe-contracts/{hpe_contract}/select-assets', [HpeContractController::class, 'selectAssets']);

    Route::put('hpe-contracts/{hpe_contract}/copy', [HpeContractController::class, 'copyHpeContract']);
    Route::patch('hpe-contracts/{hpe_contract}/submit', [HpeContractController::class, 'submitHpeContract']);
    Route::patch('hpe-contracts/{hpe_contract}/unsubmit', [HpeContractController::class, 'unsubmitHpeContract']);
    Route::patch('hpe-contracts/{hpe_contract}/activate', [HpeContractController::class, 'activateHpeContract']);
    Route::patch('hpe-contracts/{hpe_contract}/deactivate', [HpeContractController::class, 'deactivateHpeContract']);
    Route::get('hpe-contracts/{hpe_contract}/export', [HpeContractController::class, 'exportHpeContract']);
    Route::apiResource('hpe-contracts', HpeContractController::class);


    Route::group(['prefix' => 'contracts', 'as' => 'contracts.'], function () {
        /**
         * Contract State.
         */
        Route::apiResource('state', ContractStateController::class)->only(['show', 'update'])->parameters([
            'state' => 'contract'
        ]);
        Route::get('state/review/{contract}', [ContractStateController::class, 'review']);

        /**
         * Drafted Contracts.
         */
        Route::apiResource('drafted', ContractDraftedController::class, ['only' => ROUTE_RD]);
        Route::patch('drafted/{drafted}', [ContractDraftedController::class, 'activate']);
        Route::put('drafted/{drafted}', [ContractDraftedController::class, 'deactivate']);
        Route::post('drafted/submit/{drafted}', [ContractDraftedController::class, 'submit']);

        /**
         * Submitted Contracts.
         */
        Route::apiResource('submitted', ContractSubmittedController::class, ['only' => ROUTE_RD]);
        Route::patch('submitted/{submitted}', [ContractSubmittedController::class, 'activate']);
        Route::put('submitted/{submitted}', [ContractSubmittedController::class, 'deactivate']);
        Route::post('submitted/unsubmit/{submitted}', [ContractSubmittedController::class, 'unsubmit']);
    });

    Route::group(['prefix' => 'quotes', 'as' => 'quotes.'], function () {
        Route::post('handle', [QuoteFilesController::class, 'handle']);
        Route::put('/get/{quote}', [QuoteController::class, 'quote']);
        Route::get('/get/{quote}/quote-files/{file_type}', [QuoteController::class, 'downloadQuoteFile'])->where('file_type', 'price|schedule');
        Route::get('/groups/{quote}', [QuoteController::class, 'rowsGroups']);
        Route::get('/groups/{quote}/{group}', [QuoteController::class, 'showGroupDescription']);
        Route::post('/groups/{quote}', [QuoteController::class, 'storeGroupDescription']);
        Route::patch('/groups/{quote}/{group}', [QuoteController::class, 'updateGroupDescription']);
        Route::put('/groups/{quote}', [QuoteController::class, 'moveGroupDescriptionRows']);
        Route::delete('/groups/{quote}/{group}', [QuoteController::class, 'destroyGroupDescription']);
        Route::put('/groups/{quote}/select', [QuoteController::class, 'selectGroupDescription']);

        Route::get('permissions/{quote}', [QuoteController::class, 'showAuthorizedQuoteUsers']);
        Route::put('permissions/{quote}', [QuoteController::class, 'givePermissionToQuote']);

        Route::get('notes/{quote}', [QuoteNoteController::class, 'index']);
        Route::get('notes/{quote}/{quote_note}', [QuoteNoteController::class, 'show']);
        Route::post('notes/{quote}', [QuoteNoteController::class, 'store']);
        Route::patch('notes/{quote}/{quote_note}', [QuoteNoteController::class, 'update']);
        Route::delete('notes/{quote}/{quote_note}', [QuoteNoteController::class, 'destroy']);

        Route::get('tasks/create', [QuoteTaskController::class, 'showTemplate']);
        Route::put('tasks/template', [QuoteTaskController::class, 'updateTemplate']);
        Route::patch('tasks/template', [QuoteTaskController::class, 'resetTemplate']);
        Route::get('tasks/{quote}', [QuoteTaskController::class, 'paginateRescueQuoteTasks']);
        Route::get('tasks/{quote}/{task}', [QuoteTaskController::class, 'showRescueQuoteTask']);
        Route::post('tasks/{quote}', [QuoteTaskController::class, 'storeRescueQuoteTask']);
        Route::patch('tasks/{quote}/{task}', [QuoteTaskController::class, 'updateRescueQuoteTask']);
        Route::delete('tasks/{quote}/{task}', [QuoteTaskController::class, 'destroyRescueQuoteTask']);

        Route::group(['middleware' => THROTTLE_RATE_01], function () {
            Route::get('/discounts/{quote}', [QuoteController::class, 'discounts']);
            Route::post('/try-discounts/{quote}', [QuoteController::class, 'tryDiscounts']);
            Route::get('/review/{quote}', [QuoteController::class, 'review']);
            Route::post('state', [QuoteController::class, 'storeState']);
            Route::patch('version/{quote}', [QuoteController::class, 'setVersion']);

            /**
             * Drafted Quotes
             */
            Route::apiResource('drafted', QuoteDraftedController::class, ['only' => ROUTE_RD]);
            Route::patch('drafted/{drafted}', [QuoteDraftedController::class, 'activate']);
            Route::put('drafted/{drafted}', [QuoteDraftedController::class, 'deactivate']);
            Route::delete('drafted/version/{version}', [QuoteDraftedController::class, 'destroyVersion']);

            /**
             * Submitted Quotes
             */
            Route::get('submitted/pdf/{submitted}', [QuoteSubmittedController::class, 'pdf']);
            Route::get('submitted/pdf/{submitted}/contract', [QuoteSubmittedController::class, 'contractPdf']);
            Route::apiResource('submitted', QuoteSubmittedController::class, ['only' => ROUTE_RD]);
            Route::patch('submitted/{submitted}', [QuoteSubmittedController::class, 'activate']);
            Route::put('submitted/{submitted}', [QuoteSubmittedController::class, 'deactivate']);
            Route::put('submitted/copy/{submitted}', [QuoteSubmittedController::class, 'copy']);
            Route::put('submitted/unsubmit/{submitted}', [QuoteSubmittedController::class, 'unsubmit']);
            Route::post('submitted/contract/{submitted}', [QuoteSubmittedController::class, 'createContract']);
            Route::put('submitted/contract-template/{submitted}/{template}', [QuoteSubmittedController::class, 'setContractTemplate']);

            Route::apiResource('file', QuoteFilesController::class, ['only' => ROUTE_CR]);

            /**
             * Customers
             */
            Route::apiResource('customers', CustomerController::class, ['only' => ROUTE_CRD]);
            Route::patch('customers/{eq_customer}', [CustomerController::class, 'update']);

            Route::get('customers/number/{company}/{customer?}', [CustomerController::class, 'giveCustomerNumber']);

            Route::group(['prefix' => 'step'], function () {
                Route::get('1', [QuoteController::class, 'step1']);
                Route::post('1', [QuoteController::class, 'templates']);
                Route::post('2', [QuoteController::class, 'step2']);
                Route::get('3', [QuoteController::class, 'step3']);
            });
        });
    });

    Route::apiResource('ww-customers', WorldwideCustomerController::class, ['only' => ROUTE_R]);

    Route::get('opportunities', [OpportunityController::class, 'paginateOpportunities']);
    Route::get('opportunities/lost', [OpportunityController::class, 'paginateLostOpportunities']);
    Route::post('opportunities/upload', [OpportunityController::class, 'batchUploadOpportunities']);
    Route::patch('opportunities/save', [OpportunityController::class, 'batchSaveOpportunities']);
    Route::get('opportunities/{opportunity}', [OpportunityController::class, 'showOpportunity']);
    Route::post('opportunities', [OpportunityController::class, 'storeOpportunity']);
    Route::patch('opportunities/{opportunity}', [OpportunityController::class, 'updateOpportunity']);
    Route::delete('opportunities/{opportunity}', [OpportunityController::class, 'destroyOpportunity']);
    Route::patch('opportunities/{opportunity}/lost', [OpportunityController::class, 'markOpportunityAsLost']);
    Route::patch('opportunities/{opportunity}/restore-from-lost', [OpportunityController::class, 'markOpportunityAsNotLost']);

    Route::get('opportunity-template', [OpportunityTemplateController::class, 'showOpportunityTemplate']);
    Route::put('opportunity-template', [OpportunityTemplateController::class, 'updateOpportunityTemplate']);

    /**
     * Sales Orders.
     */
    Route::get('sales-orders/drafted', SalesOrderDraftedController::class);
    Route::get('sales-orders/submitted', SalesOrderSubmittedController::class);

    Route::get('sales-orders/cancel-reasons', [SalesOrderController::class, 'showCancelSalesOrderReasonsList']);
    Route::get('sales-orders/{sales_order}', [SalesOrderController::class, 'showSalesOrderState']);
    Route::get('sales-orders/{sales_order}/preview', [SalesOrderController::class, 'showSalesOrderPreviewData']);
    Route::get('sales-orders/{sales_order}/export', [SalesOrderController::class, 'exportSalesOrder']);
    Route::post('sales-orders', [SalesOrderController::class, 'draftSalesOrder']);
    Route::post('sales-orders/{sales_order}/submit', [SalesOrderController::class, 'submitSalesOrder']);
    Route::patch('sales-orders/{sales_order}', [SalesOrderController::class, 'updateSalesOrder']);
    Route::patch('sales-orders/{sales_order}/unravel', [SalesOrderController::class, 'unravelSalesOrder']);
    Route::delete('sales-orders/{sales_order}', [SalesOrderController::class, 'deleteSalesOrder']);
    Route::patch('sales-orders/{sales_order}/activate', [SalesOrderController::class, 'activateSalesOrder']);
    Route::patch('sales-orders/{sales_order}/deactivate', [SalesOrderController::class, 'deactivateSalesOrder']);
    Route::patch('sales-orders/{sales_order}/cancel', [SalesOrderController::class, 'cancelSalesOrder']);

    /**
     *  Worldwide Quotes.
     */
    Route::get('ww-quotes/drafted', WorldwideQuoteDraftedController::class);
    Route::get('ww-quotes/drafted/dead', [WorldwideQuoteDraftedController::class, 'paginateDeadDraftedQuotes']);
    Route::get('ww-quotes/submitted', WorldwideQuoteSubmittedController::class);
    Route::get('ww-quotes/submitted/dead', [WorldwideQuoteSubmittedController::class, 'paginateDeadSubmittedQuotes']);


    Route::post('ww-quotes', [WorldwideQuoteController::class, 'initializeQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/versions/{version:id}', [WorldwideQuoteController::class, 'switchActiveVersionOfQuote']);
    Route::delete('ww-quotes/{worldwide_quote}/versions/{version:id}', [WorldwideQuoteController::class, 'destroyQuoteVersion']);
    Route::get('ww-quotes/{worldwide_quote}', [WorldwideQuoteController::class, 'showQuoteState']);

    Route::get('ww-quotes/{worldwide_quote}/export', [WorldwideQuoteController::class, 'exportQuote']);
    Route::post('ww-quotes/{worldwide_quote}/submit', [WorldwideQuoteController::class, 'submitQuote']);
    Route::post('ww-quotes/{worldwide_quote}/draft', [WorldwideQuoteController::class, 'draftQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/unravel', [WorldwideQuoteController::class, 'unravelQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/activate', [WorldwideQuoteController::class, 'activateQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/deactivate', [WorldwideQuoteController::class, 'deactivateQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/dead', [WorldwideQuoteController::class, 'markQuoteAsDead']);
    Route::patch('ww-quotes/{worldwide_quote}/restore-from-dead', [WorldwideQuoteController::class, 'markQuoteAsAlive']);
    Route::get('ww-quotes/{worldwide_quote}/files/distributor-files', [WorldwideQuoteController::class, 'downloadQuoteDistributorFiles']);
    Route::get('ww-quotes/{worldwide_quote}/files/schedule-files', [WorldwideQuoteController::class, 'downloadQuoteScheduleFiles']);

    Route::get('ww-quotes/{worldwide_quote}/notes', [WorldwideQuoteNoteController::class, 'paginateQuoteNotes']);
    Route::get('ww-quotes/{worldwide_quote}/notes/{worldwide_quote_note:id}', [WorldwideQuoteNoteController::class, 'showQuoteNote']);
    Route::post('ww-quotes/{worldwide_quote}/notes', [WorldwideQuoteNoteController::class, 'storeQuoteNote']);
    Route::patch('ww-quotes/{worldwide_quote}/notes/{worldwide_quote_note:id}', [WorldwideQuoteNoteController::class, 'updateQuoteNote']);
    Route::delete('ww-quotes/{worldwide_quote}/notes/{worldwide_quote_note:id}', [WorldwideQuoteNoteController::class, 'destroyQuoteNote']);

    Route::get('ww-quotes/{worldwide_quote}/tasks', [QuoteTaskController::class, 'paginateWorldwideQuoteTasks']);
    Route::get('ww-quotes/{worldwide_quote}/tasks/{task:id}', [QuoteTaskController::class, 'showWorldwideQuoteTask']);
    Route::post('ww-quotes/{worldwide_quote}/tasks', [QuoteTaskController::class, 'storeWorldwideQuoteTask']);
    Route::patch('ww-quotes/{worldwide_quote}/tasks/{task:id}', [QuoteTaskController::class, 'updateWorldwideQuoteTask']);
    Route::delete('ww-quotes/{worldwide_quote}/tasks/{task:id}', [QuoteTaskController::class, 'destroyWorldwideQuoteTask']);

    Route::get('ww-quotes/{worldwide_quote}/sales-order-data', [WorldwideQuoteController::class, 'showSalesOrderDataOfWorldwideQuote']);

    /**
     * Worldwide Pack Quote.
     */
    Route::post('ww-quotes/{worldwide_quote}/assets', [WorldwideQuoteAssetController::class, 'initializeQuoteAsset']);
    Route::patch('ww-quotes/{worldwide_quote}/assets', [WorldwideQuoteAssetController::class, 'batchUpdateQuoteAssets']);
    Route::delete('ww-quotes/{worldwide_quote}/assets/{asset:id}', [WorldwideQuoteAssetController::class, 'destroyQuoteAsset']);
    Route::post('ww-quotes/{worldwide_quote}/assets/lookup', [WorldwideQuoteAssetController::class, 'batchWarrantyLookup']);
    Route::post('ww-quotes/{worldwide_quote}/assets/upload', [WorldwideQuoteAssetController::class, 'uploadBatchQuoteAssetsFile']);
    Route::post('ww-quotes/{worldwide_quote}/assets/import', [WorldwideQuoteAssetController::class, 'importBatchQuoteAssetsFile']);

    Route::post('ww-quotes/{worldwide_quote}/contacts', [WorldwideQuoteController::class, 'processQuoteAddressesContactsStep']);
    Route::post('ww-quotes/{worldwide_quote}/assets-review', [WorldwideQuoteController::class, 'processQuoteAssetsReviewStep']);
    Route::post('ww-quotes/{worldwide_quote}/margin', [WorldwideQuoteController::class, 'processQuoteMarginStep']);
    Route::get('ww-quotes/{worldwide_quote}/applicable-discounts', [WorldwideQuoteController::class, 'showPackQuoteApplicableDiscounts']);
    Route::post('ww-quotes/{worldwide_quote}/discounts', [WorldwideQuoteController::class, 'processQuoteDiscountStep']);
    Route::post('ww-quotes/{worldwide_quote}/details', [WorldwideQuoteController::class, 'processQuoteDetailsStep']);

    Route::post('ww-quotes/{worldwide_quote}/contract/country-margin-tax-price-summary', [WorldwideQuoteController::class, 'showPriceSummaryOfContractQuoteAfterCountryMarginTax']);
    Route::post('ww-quotes/{worldwide_quote}/pack/country-margin-tax-price-summary', [WorldwideQuoteController::class, 'showPriceSummaryOfPackQuoteAfterCountryMarginTax']);

    Route::post('ww-quotes/{worldwide_quote}/contract/discounts-price-summary', [WorldwideQuoteController::class, 'showPriceSummaryOfContractQuoteAfterDiscounts']);
    Route::post('ww-quotes/{worldwide_quote}/pack/discounts-price-summary', [WorldwideQuoteController::class, 'showPriceSummaryOfPackQuoteAfterDiscounts']);

    Route::get('ww-quotes/{worldwide_quote}/validate', [WorldwideQuoteController::class, 'validateQuote']);

    /**
     * Worldwide Contract Quote.
     */
    Route::post('ww-quotes/{worldwide_quote}/import', [WorldwideQuoteController::class, 'processQuoteImportStep']);
    Route::delete('ww-quotes/{worldwide_quote}', [WorldwideQuoteController::class, 'destroyQuote']);
    Route::get('ww-quotes/{worldwide_quote}/preview', [WorldwideQuoteController::class, 'showQuotePreviewData']);

    Route::post('ww-distributions', [WorldwideDistributionController::class, 'initializeDistribution']);
    Route::post('ww-distributions/handle', [WorldwideDistributionController::class, 'processDistributions']);
    Route::post('ww-distributions/mapping', [WorldwideDistributionController::class, 'updateDistributionsMapping']);
    Route::post('ww-distributions/mapping-review', [WorldwideDistributionController::class, 'updateRowsSelection']);
    Route::post('ww-distributions/margin', [WorldwideDistributionController::class, 'setDistributionsMargin']);
    Route::post('ww-distributions/discounts', [WorldwideDistributionController::class, 'applyDiscounts']);
    Route::post('ww-distributions/details', [WorldwideDistributionController::class, 'updateDetails']);
    Route::delete('ww-distributions/{worldwide_distribution}', [WorldwideDistributionController::class, 'destroy']);

    Route::post('ww-distributions/{worldwide_distribution}/distributor-file', [WorldwideDistributionController::class, 'storeDistributorFile']);
    Route::post('ww-distributions/{worldwide_distribution}/schedule-file', [WorldwideDistributionController::class, 'storeScheduleFile']);

    Route::post('ww-distributions/{worldwide_distribution}/rows-groups', [WorldwideDistributionController::class, 'createRowsGroup']);
    Route::patch('ww-distributions/{worldwide_distribution}/rows-groups/{rows_group:id}', [WorldwideDistributionController::class, 'updateRowsGroup']);
    Route::delete('ww-distributions/{worldwide_distribution}/rows-groups/{rows_group:id}', [WorldwideDistributionController::class, 'deleteRowsGroup']);
    Route::put('ww-distributions/{worldwide_distribution}/rows-groups', [WorldwideDistributionController::class, 'moveRowsBetweenGroups']);
    Route::post('ww-distributions/{worldwide_distribution}/rows-lookup', [WorldwideDistributionController::class, 'performRowsLookup']);
    Route::get('ww-distributions/{worldwide_distribution}/applicable-discounts', [WorldwideDistributionController::class, 'showDistributionApplicableDiscounts']);
    Route::post('ww-distributions/{worldwide_distribution}/discounts-margin', [WorldwideDistributionController::class, 'showMarginAfterPredefinedDiscounts']);
    Route::post('ww-distributions/{worldwide_distribution}/custom-discount-margin', [WorldwideDistributionController::class, 'showMarginAfterCustomDiscount']);
    Route::post('ww-distributions/{worldwide_distribution}/country-margin-tax-margin', [WorldwideDistributionController::class, 'showPriceSummaryAfterMarginTax']);

    Route::patch('ww-distributions/{worldwide_distribution}/mapped-rows/{mapped_row:id}', [WorldwideDistributionController::class, 'updateMappedRow']);


    /**
     * Unified Quotes (Rescue & Worldwide).
     */
    Route::get('unified-quotes/expiring', [UnifiedQuoteController::class, 'paginateUnifiedExpiringQuotes']);
    Route::get('unified-quotes/submitted', [UnifiedQuoteController::class, 'paginateUnifiedSubmittedQuotes']);
    Route::get('unified-quotes/drafted', [UnifiedQuoteController::class, 'paginateUnifiedDraftedQuotes']);
});


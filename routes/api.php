<?php

use App\Domain\Activity\Controllers\V1\ActivityController;
use App\Domain\Address\Controllers\V1\AddressController;
use App\Domain\Appointment\Controllers\V1\AppointmentController;
use App\Domain\Asset\Controllers\V1\AssetController;
use App\Domain\Asset\Controllers\V1\AssetOwnershipController;
use App\Domain\Attachment\Controllers\V1\AttachmentController;
use App\Domain\Authentication\Controllers\V1\AuthController;
use App\Domain\Authorization\Controllers\V1\PermissionController;
use App\Domain\Authorization\Controllers\V1\RoleController;
use App\Domain\BusinessDivision\Controllers\V1\BusinessDivisionController;
use App\Domain\Company\Controllers\V1\CompanyController;
use App\Domain\Company\Controllers\V1\CompanyNoteController;
use App\Domain\Company\Controllers\V1\CompanyOwnershipController;
use App\Domain\Contact\Controllers\V1\ContactController;
use App\Domain\ContractType\Controllers\V1\ContractTypeController;
use App\Domain\Country\Controllers\V1\CountryController;
use App\Domain\Currency\Controllers\V1\CurrencyController;
use App\Domain\CustomField\Controllers\V1\CustomFieldController;
use App\Domain\DataAllocation\Controllers\V1\DataAllocationController;
use App\Domain\Date\Controllers\V1\DateFormatController;
use App\Domain\Discount\Controllers\V1\MultiYearDiscountController;
use App\Domain\Discount\Controllers\V1\PrePayDiscountController;
use App\Domain\Discount\Controllers\V1\PromotionalDiscountController;
use App\Domain\Discount\Controllers\V1\SNDcontroller;
use App\Domain\DocumentEngine\Controllers\V1\DocumentEngineDataController;
use App\Domain\DocumentEngine\Controllers\V1\DocumentEngineEventController;
use App\Domain\ExchangeRate\Controllers\V1\ExchangeRateController;
use App\Domain\HpeContract\Controllers\V1\HpeContractController;
use App\Domain\HpeContract\Controllers\V1\HpeContractFileController;
use App\Domain\Invitation\Controllers\V1\InvitationController;
use App\Domain\Language\Controllers\V1\LanguageController;
use App\Domain\Maintenance\Controllers\V1\MaintenanceController;
use App\Domain\Margin\Controllers\V1\CountryMarginController;
use App\Domain\Note\Controllers\NoteController;
use App\Domain\Notification\Controllers\V1\NotificationController;
use App\Domain\Pipeline\Controllers\V1\PipelineController;
use App\Domain\Pipeliner\Controllers\V1\PipelinerController;
use App\Domain\Pipeliner\Controllers\V1\PipelinerWebhookController;
use App\Domain\QuoteFile\Controllers\V1\FileFormatsController;
use App\Domain\QuoteFile\Controllers\V1\ImportableColumnController;
use App\Domain\Rescue\Controllers\V1\Contract\ContractDraftedController;
use App\Domain\Rescue\Controllers\V1\Contract\ContractStateController;
use App\Domain\Rescue\Controllers\V1\Contract\ContractSubmittedController;
use App\Domain\Rescue\Controllers\V1\Contract\UnifiedContractController;
use App\Domain\Rescue\Controllers\V1\Customer\S4QuoteController;
use App\Domain\Rescue\Controllers\V1\CustomerController;
use App\Domain\Rescue\Controllers\V1\QuoteController;
use App\Domain\Rescue\Controllers\V1\QuoteDraftedController;
use App\Domain\Rescue\Controllers\V1\QuoteFileController;
use App\Domain\Rescue\Controllers\V1\QuoteNoteController;
use App\Domain\Rescue\Controllers\V1\QuoteSubmittedController;
use App\Domain\Rescue\Controllers\V1\QuoteTaskController;
use App\Domain\Rescue\Controllers\V1\RescueQuoteAttachmentController;
use App\Domain\SalesUnit\Controllers\V1\SalesUnitController;
use App\Domain\Settings\Controllers\V1\SystemSettingController;
use App\Domain\Space\Controllers\V1\SpaceController;
use App\Domain\Stats\Controllers\V1\StatsController;
use App\Domain\Task\Controllers\V1\TaskController;
use App\Domain\Team\Controllers\V1\TeamController;
use App\Domain\Template\Controllers\V1\ContractTemplateController;
use App\Domain\Template\Controllers\V1\HpeContractTemplateController;
use App\Domain\Template\Controllers\V1\OpportunityFormController;
use App\Domain\Template\Controllers\V1\QuoteTemplateController;
use App\Domain\Template\Controllers\V1\SalesOrderTemplateController;
use App\Domain\Timezone\Controllers\V1\TimezoneController;
use App\Domain\UnifiedQuote\Controllers\V1\UnifiedQuoteController;
use App\Domain\User\Controllers\V1\UserController;
use App\Domain\Vendor\Controllers\V1\VendorController;
use App\Domain\VendorServices\Controllers\V1\ServiceController;
use App\Domain\Worldwide\Controllers\V1\Opportunity\OpportunityAttachmentController;
use App\Domain\Worldwide\Controllers\V1\Opportunity\OpportunityController;
use App\Domain\Worldwide\Controllers\V1\Opportunity\OpportunityNoteController;
use App\Domain\Worldwide\Controllers\V1\Opportunity\OpportunityOwnershipController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideCustomerController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideDistributionController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteAssetController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteAttachmentController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteDraftedController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteNoteController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteOwnershipController;
use App\Domain\Worldwide\Controllers\V1\Quote\WorldwideQuoteSubmittedController;
use App\Domain\Worldwide\Controllers\V1\SalesOrder\SalesOrderController;
use App\Domain\Worldwide\Controllers\V1\SalesOrder\SalesOrderDraftedController;
use App\Domain\Worldwide\Controllers\V1\SalesOrder\SalesOrderSubmittedController;
use App\Foundation\Support\Elasticsearch\Controllers\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::get('settings/public', [SystemSettingController::class, 'showPublicSettings']);

Route::group(['prefix' => 'auth', 'middleware' => THROTTLE_RATE_01], function () {
    Route::get('attempts/{email}', [AuthController::class, 'showAttempts']);

    Route::post('signin', [AuthController::class, 'signin'])->name('signin');
    Route::post('logout-user', [AuthController::class, 'authenticateAndLogout']);

    Route::get('signup/{invitation:invitation_token}', [AuthController::class, 'showInvitation']);
    Route::post('signup/{invitation:invitation_token}', [UserController::class, 'registerUser']);

    Route::get('reset-password/{reset}', [AuthController::class, 'verifyPasswordReset']);
    Route::post('reset-password/{reset}', [AuthController::class, 'resetPassword']);

    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('logout', [AuthController::class, 'logout'])->name('account.logout');
        Route::match(['get', 'put'], 'user', [AuthController::class, 'showCurrentUser'])->name('account.show');
        Route::post('user', [AuthController::class, 'updateCurrentUser'])->name('account.update');
    });
});

/* Maintenance. */
Route::get('maintenance', [MaintenanceController::class, 'show']);
Route::group(['middleware' => ['auth:api', 'role:Administrator']], function () {
    Route::post('maintenance', [MaintenanceController::class, 'start']);
    Route::put('maintenance', [MaintenanceController::class, 'stop']);
});

Route::group(['prefix' => 'data'], function () {
    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('timezones', TimezoneController::class);
        Route::get('languages', LanguageController::class);
        Route::get('currencies', CurrencyController::class);
        Route::get('currencies/xr', [CurrencyController::class, 'showAllHavingExrate']);
        Route::get('fileformats', FileFormatsController::class);
        Route::get('dateformats', DateFormatController::class);
    });

    Route::get('countries', CountryController::class);
});

Route::patch('exchange-rates/refresh', [ExchangeRateController::class, 'refreshExchangeRates']);
Route::post('exchange-rates/convert', [ExchangeRateController::class, 'convertCurrencies']);

Route::group(['prefix' => 's4', 'as' => 's4.', 'middleware' => [THROTTLE_RATE_01]], function () {
    Route::get('quotes/{rfq}', [S4QuoteController::class, 'showQuoteByRfqNumber'])->name('quote');
    Route::post('quotes', [S4QuoteController::class, 'storeS4Customer'])->name('store');
    Route::get('quotes/{rfq}/price', [S4QuoteController::class, 'downloadPriceListFile'])->name('price');
    Route::get('quotes/{rfq}/schedule', [S4QuoteController::class, 'downloadPaymentScheduleFile'])->name('schedule');
    Route::get('quotes/{rfq}/pdf', [S4QuoteController::class, 'exportToPdf'])->name('pdf');
});

Route::group(['prefix' => 'document-engine'], function () {
    Route::post('events', [DocumentEngineEventController::class, 'handleDocumentEngineEvent']);
    Route::get('document-headers/linked', [DocumentEngineDataController::class, 'showLinkedDocumentHeaders']);
});

Route::group(['middleware' => 'auth:api'], function () {
    Route::get('business-divisions', BusinessDivisionController::class);
    Route::get('contract-types', ContractTypeController::class);

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::match(['get', 'post'], 'stats', [StatsController::class, 'showSummaryOfQuotes']);
        Route::match(['get', 'post'], 'stats/customers', [StatsController::class, 'showSummaryOfCustomers']);
        Route::post('stats/customers/map', [StatsController::class, 'showCustomerLocations']);
        Route::post('stats/assets/map', [StatsController::class, 'showAssetLocations']);
        Route::post('stats/quotes/map', [StatsController::class, 'showQuoteLocations']);
        Route::get('stats/locations/{location}/quotes', [StatsController::class, 'showQuotesOfLocation']);

        Route::post('attachments', AttachmentController::class);
        Route::get('attachments/{attachment}/download', [AttachmentController::class, 'downloadAttachment']);

        Route::resource('assets', AssetController::class)->only(ROUTE_CRUD);
        Route::post('assets/unique', [AssetController::class, 'checkUniqueness']);
        Route::post('lookup/service', ServiceController::class);
        Route::get('assets/{asset}/companies', [AssetController::class, 'showCompaniesOfAsset']);
        Route::patch('assets/{asset}/ownership', [AssetOwnershipController::class, 'changeAssetOwnership']);
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

        Route::get('contacts/{contact}/appointments', [ContactController::class, 'showAppointmentsOfContact']);
        Route::put('contacts/{contact}/address/{address}', [ContactController::class, 'associateContactWithAddress']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::apiResource('notifications', NotificationController::class, ['only' => ['index', 'destroy']]);
        Route::get('notifications/latest', [NotificationController::class, 'latest']);
        Route::delete('notifications', [NotificationController::class, 'destroyAll']);
        Route::put('notifications/{notification}', [NotificationController::class, 'read']);
        Route::put('notifications', [NotificationController::class, 'readAll']);

        Route::apiResource('settings', SystemSettingController::class, ['only' => ROUTE_RU]);
        Route::patch('settings', [SystemSettingController::class, 'updateMany']);

        Route::patch('search/queue-rebuild', [SearchController::class, 'queueSearchRebuild']);

        Route::match(['get', 'post'], 'activities', [ActivityController::class, 'paginateActivities']);
        Route::get('activities/meta', [ActivityController::class, 'showActivityLogMetaData']);
        Route::match(
            ['get', 'post'],
            'activities/subject/{subject}',
            [ActivityController::class, 'paginateActivitiesOfSubject']
        );

        Route::match(['get', 'post'], 'activities/export/pdf', [ActivityController::class, 'exportActivityLogToPdf']);
        Route::match(['get', 'post'], 'activities/export/csv', [ActivityController::class, 'exportActivityLogToCsv']);

        Route::match(
            ['get', 'post'],
            'activities/subject/{subject}/export/pdf',
            [ActivityController::class, 'exportActivityLogOfSubjectToPdf']
        );
        Route::match(
            ['get', 'post'],
            'activities/subject/{subject}/export/csv',
            [ActivityController::class, 'exportActivityLogOfSubjectToCsv']
        );

        Route::apiResource('importable-columns', ImportableColumnController::class);
        Route::put('importable-columns/activate/{importable_column}', [ImportableColumnController::class, 'activate']);
        Route::put(
            'importable-columns/deactivate/{importable_column}',
            [ImportableColumnController::class, 'deactivate']
        );

        Route::get('custom-fields', [CustomFieldController::class, 'showListOfCustomFields']);
        Route::get(
            'custom-field-values/{custom_field:field_name}',
            [CustomFieldController::class, 'showValuesOfCustomFieldByFieldName']
        );
        Route::put(
            'custom-field-values/{custom_field:field_name}',
            [CustomFieldController::class, 'updateValuesOfCustomField']
        );
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
        Route::get('invitations', [InvitationController::class, 'index']);
        Route::get('invitations/{invitation}', [InvitationController::class, 'show']);
        Route::delete('invitations/{invitation}', [InvitationController::class, 'destroy']);
        Route::put('invitations/resend/{invitation}', [InvitationController::class, 'resend']);
        Route::put('invitations/cancel/{invitation}', [InvitationController::class, 'cancel']);

        Route::resource('roles', RoleController::class, ['only' => ROUTE_CRUD]);
        Route::put('roles/activate/{role}', [RoleController::class, 'activate']);
        Route::put('roles/deactivate/{role}', [RoleController::class, 'deactivate']);
        Route::get('roles/module/{module}', [RoleController::class, 'module']);

        Route::put('permissions/module', [PermissionController::class, 'grantModulePermission']);
        Route::get('permissions/module/{module}', [PermissionController::class, 'showModulePermissionForm']);

        /*
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
        Route::get('templates/designer/{template}', [QuoteTemplateController::class, 'showTemplateForm']);
        Route::get('templates/country/{country}', [QuoteTemplateController::class, 'filterTemplatesByCountry']);
        Route::apiResource('templates', QuoteTemplateController::class);
        Route::put('templates/activate/{template}', [QuoteTemplateController::class, 'activate']);
        Route::put('templates/deactivate/{template}', [QuoteTemplateController::class, 'deactivate']);
        Route::put('templates/copy/{template}', [QuoteTemplateController::class, 'copy']);

        Route::post('templates/filter', [QuoteTemplateController::class, 'filterRescueTemplates']);
        Route::post('templates/filter-ww', [QuoteTemplateController::class, 'filterWorldwideTemplates']);
        Route::post('templates/filter-ww/pack', [QuoteTemplateController::class, 'filterWorldwidePackTemplates']);
        Route::post(
            'templates/filter-ww/contract',
            [QuoteTemplateController::class, 'filterWorldwideContractTemplates']
        );

        Route::get(
            'contract-templates/designer/{contract_template}',
            [ContractTemplateController::class, 'showTemplateForm']
        );
        Route::get('contract-templates/country/{country}', [ContractTemplateController::class, 'country']);
        Route::apiResource('contract-templates', ContractTemplateController::class);
        Route::put('contract-templates/activate/{contract_template}', [ContractTemplateController::class, 'activate']);
        Route::put(
            'contract-templates/deactivate/{contract_template}',
            [ContractTemplateController::class, 'deactivate']
        );
        Route::put('contract-templates/copy/{contract_template}', [ContractTemplateController::class, 'copy']);

        Route::post(
            'contract-templates/filter-ww/pack',
            [ContractTemplateController::class, 'filterWorldwidePackContractTemplates']
        );
        Route::post(
            'contract-templates/filter-ww/contract',
            [ContractTemplateController::class, 'filterWorldwideContractContractTemplates']
        );

        Route::get(
            'hpe-contract-templates/designer/{hpe_contract_template}',
            [HpeContractTemplateController::class, 'showTemplateSchema']
        );
        Route::get(
            'hpe-contract-templates/country/{country}',
            [HpeContractTemplateController::class, 'filterTemplatesByCountry']
        );
        Route::post('hpe-contract-templates/filter', [HpeContractTemplateController::class, 'filterTemplates']);
        Route::get('hpe-contract-templates', [HpeContractTemplateController::class, 'paginateTemplates']);
        Route::get(
            'hpe-contract-templates/{hpe_contract_template}',
            [HpeContractTemplateController::class, 'showTemplate']
        );
        Route::post('hpe-contract-templates', [HpeContractTemplateController::class, 'storeTemplate']);
        Route::patch(
            'hpe-contract-templates/{hpe_contract_template}',
            [HpeContractTemplateController::class, 'updateTemplate']
        );
        Route::delete(
            'hpe-contract-templates/{hpe_contract_template}',
            [HpeContractTemplateController::class, 'destroyTemplate']
        );
        Route::put(
            'hpe-contract-templates/activate/{hpe_contract_template}',
            [HpeContractTemplateController::class, 'activateTemplate']
        );
        Route::put(
            'hpe-contract-templates/deactivate/{hpe_contract_template}',
            [HpeContractTemplateController::class, 'deactivateTemplate']
        );
        Route::put(
            'hpe-contract-templates/copy/{hpe_contract_template}',
            [HpeContractTemplateController::class, 'replicateTemplate']
        );

        Route::get('sales-order-templates', [SalesOrderTemplateController::class, 'paginateSalesOrderTemplates']);
        Route::get(
            'sales-order-templates/{sales_order_template}',
            [SalesOrderTemplateController::class, 'showSalesOrderTemplate']
        );
        Route::get(
            'sales-order-templates/{sales_order_template}/form',
            [SalesOrderTemplateController::class, 'showTemplateForm']
        );
        Route::post('sales-order-templates', [SalesOrderTemplateController::class, 'storeSalesOrderTemplate']);
        Route::patch(
            'sales-order-templates/{sales_order_template}',
            [SalesOrderTemplateController::class, 'updateSalesOrderTemplate']
        );
        Route::patch(
            'sales-order-templates/{sales_order_template}/schema',
            [SalesOrderTemplateController::class, 'updateSchemaOfSalesOrderTemplate']
        );
        Route::delete(
            'sales-order-templates/{sales_order_template}',
            [SalesOrderTemplateController::class, 'destroySalesOrderTemplate']
        );
        Route::put(
            'sales-order-templates/{sales_order_template}/activate',
            [SalesOrderTemplateController::class, 'markAsActiveSalesOrderTemplate']
        );
        Route::put(
            'sales-order-templates/{sales_order_template}/deactivate',
            [SalesOrderTemplateController::class, 'markAsInactiveSalesOrderTemplate']
        );
        Route::put(
            'sales-order-templates/{sales_order_template}/copy',
            [SalesOrderTemplateController::class, 'replicateSalesOrderTemplate']
        );
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('notes/rel-company/{related}', [NoteController::class, 'paginateCompanyNotes']);
        Route::get('notes/rel-quote/{related}', [NoteController::class, 'paginateRescueQuoteNotes']);
        Route::get('notes/rel-ww-quote/{related}', [NoteController::class, 'paginateWorldwideQuoteNotes']);
        Route::get('notes/rel-opportunity/{related}', [NoteController::class, 'paginateOpportunityNotes']);

        Route::post('notes/rel-company/{related}', [NoteController::class, 'storeCompanyNote']);
        Route::post('notes/rel-quote/{related}', [NoteController::class, 'storeRescueQuoteNote']);
        Route::post('notes/rel-ww-quote/{related}', [NoteController::class, 'storeWorldwideQuoteNote']);
        Route::post('notes/rel-opportunity/{related}', [NoteController::class, 'storeOpportunityNote']);

        Route::get('notes/{note}', [NoteController::class, 'showNote']);
        Route::patch('notes/{note}', [NoteController::class, 'updateNote']);
        Route::delete('notes/{note}', [NoteController::class, 'deleteNote']);
    });

    Route::group(['middleware' => THROTTLE_RATE_01], function () {
        Route::get('companies/external', [CompanyController::class, 'getExternal']);
        Route::get('companies/internal', [CompanyController::class, 'showListOfInternalCompanies']);
        Route::get('companies/countries', [CompanyController::class, 'showInternalCompaniesWithCountries']);
        Route::get('external-companies', [CompanyController::class, 'paginateExternalCompanies']);

        Route::get('companies', [CompanyController::class, 'paginateCompanies']);
        Route::get('companies/filters', [CompanyController::class, 'showCompanyFilters']);
        Route::get('companies/create', [CompanyController::class, 'showCompanyFormData']);
        Route::get('companies/{company}', [CompanyController::class, 'showCompany']);
        Route::post('companies', [CompanyController::class, 'storeCompany']);
        Route::patch('companies/{company}', [CompanyController::class, 'updateCompany']);
        Route::patch('companies/partial/{company}', [CompanyController::class, 'partiallyUpdateCompany']);
        Route::delete('companies/{company}', [CompanyController::class, 'destroyCompany']);

        Route::patch('companies/{company}/ownership', [CompanyOwnershipController::class, 'changeCompanyOwnership']);

        Route::get('companies/{company}/opportunities', [CompanyController::class, 'showOpportunitiesOfCompany']);
        Route::get('companies/{company}/quotes', [CompanyController::class, 'showQuotesOfCompany']);
        Route::get('companies/{company}/sales-orders', [CompanyController::class, 'showSalesOrdersOfCompany']);
        Route::get('companies/{company}/assets', [CompanyController::class, 'showAssetsOfCompany']);

        Route::patch(
            'companies/{company}/addresses/{address}/attach',
            [CompanyController::class, 'attachAddressToCompany']
        );
        Route::patch(
            'companies/{company}/addresses/{address}/detach',
            [CompanyController::class, 'detachAddressFromCompany']
        )->scopeBindings();

        Route::patch(
            'companies/{company}/contacts/{contact}/attach',
            [CompanyController::class, 'attachContactToCompany']
        );
        Route::patch(
            'companies/{company}/contacts/{contact}/detach',
            [CompanyController::class, 'detachContactFromCompany']
        )->scopeBindings();

        Route::get('companies/{company}/notes', [CompanyController::class, 'showUnifiedNotesOfCompany']);
        Route::post('companies/{company}/company-notes', [CompanyNoteController::class, 'storeCompanyNote']);
        Route::get('companies/company-notes/{note}', [CompanyNoteController::class, 'showCompanyNote']);
        Route::patch('companies/company-notes/{note}', [CompanyNoteController::class, 'updateCompanyNote']);
        Route::delete('companies/company-notes/{note}', [CompanyNoteController::class, 'deleteCompanyNote']);

        Route::put('companies/activate/{company}', [CompanyController::class, 'markAsActiveCompany']);
        Route::put('companies/deactivate/{company}', [CompanyController::class, 'markAsInactiveCompany']);

        Route::patch('companies/{company}/contacts/{contact:id}', [CompanyController::class, 'updateCompanyContact']);

        Route::get('companies/{company}/attachments', [CompanyController::class, 'showAttachmentsOfCompany']);
        Route::post('companies/{company}/attachments', [CompanyController::class, 'storeAttachmentForCompany']);
        Route::delete(
            'companies/{company}/attachments/{attachment:id}',
            [CompanyController::class, 'deleteAttachmentOfCompany']
        );

        Route::get('companies/{company}/appointments', [CompanyController::class, 'showAppointmentsOfCompany']);
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
    Route::patch(
        'hpe-contracts/{hpe_contract}/import/{hpe_contract_file}',
        [HpeContractController::class, 'importHpeContract']
    );
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
        Route::get('drafted/users', [UnifiedContractController::class, 'showUserNamesOfDraftedContracts']);
        Route::get('submitted/users', [UnifiedContractController::class, 'showUserNamesOfSubmittedContracts']);

        Route::get('drafted/customers', [UnifiedContractController::class, 'showCustomerNamesOfDraftedContracts']);
        Route::get('submitted/customers', [UnifiedContractController::class, 'showCustomerNamesOfSubmittedContracts']);

        Route::get('drafted/companies', [UnifiedContractController::class, 'showCompanyNamesOfDraftedContracts']);
        Route::get('submitted/companies', [UnifiedContractController::class, 'showCompanyNamesOfSubmittedContracts']);

        Route::get('drafted/contract-numbers', [UnifiedContractController::class, 'showNumbersOfDraftedContracts']);
        Route::get('submitted/contract-numbers', [UnifiedContractController::class, 'showNumbersOfSubmittedContracts']);

        /*
         * Contract State.
         */
        Route::apiResource('state', ContractStateController::class)->only(['show', 'update'])->parameters([
            'state' => 'contract',
        ]);
        Route::get('state/review/{contract}', [ContractStateController::class, 'review']);

        /*
         * Drafted Contracts.
         */
        Route::apiResource('drafted', ContractDraftedController::class, ['only' => ROUTE_RD]);
        Route::patch('drafted/{drafted}', [ContractDraftedController::class, 'activate']);
        Route::put('drafted/{drafted}', [ContractDraftedController::class, 'deactivate']);
        Route::post('drafted/submit/{drafted}', [ContractDraftedController::class, 'submit']);

        /*
         * Submitted Contracts.
         */
        Route::apiResource('submitted', ContractSubmittedController::class, ['only' => ROUTE_RD]);
        Route::patch('submitted/{submitted}', [ContractSubmittedController::class, 'activate']);
        Route::put('submitted/{submitted}', [ContractSubmittedController::class, 'deactivate']);
        Route::post('submitted/unsubmit/{submitted}', [ContractSubmittedController::class, 'unsubmit']);
    });

    Route::group(['prefix' => 'quotes', 'as' => 'quotes.'], function () {
        Route::post('handle', [QuoteFileController::class, 'processQuoteFile']);
        Route::put('/get/{quote}', [QuoteController::class, 'quote']);
        Route::get('/get/{quote}/quote-files/{file_type}', [QuoteController::class, 'downloadQuoteFile'])
            ->where('file_type', 'price|schedule');
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
        Route::get('notes/{quote}/{note}', [QuoteNoteController::class, 'show']);
        Route::post('notes/{quote}', [QuoteNoteController::class, 'store']);
        Route::patch('notes/{quote}/{note}', [QuoteNoteController::class, 'update']);
        Route::delete('notes/{quote}/{note}', [QuoteNoteController::class, 'destroy']);

        Route::get('tasks/create', [QuoteTaskController::class, 'showTemplate']);
        Route::put('tasks/template', [QuoteTaskController::class, 'updateTemplate']);
        Route::patch('tasks/template', [QuoteTaskController::class, 'resetTemplate']);
        Route::get('tasks/{quote}', [QuoteTaskController::class, 'paginateRescueQuoteTasks']);
        Route::get('tasks/{quote}/{task}', [QuoteTaskController::class, 'showRescueQuoteTask']);
        Route::post('tasks/{quote}', [QuoteTaskController::class, 'storeRescueQuoteTask']);
        Route::patch('tasks/{quote}/{task}', [QuoteTaskController::class, 'updateRescueQuoteTask']);
        Route::delete('tasks/{quote}/{task}', [QuoteTaskController::class, 'destroyRescueQuoteTask']);

        Route::get('{quote}/appointments', [QuoteController::class, 'showAppointmentsOfQuote']);

        Route::group(['middleware' => THROTTLE_RATE_01], function () {
            Route::get('/discounts/{quote}', [QuoteController::class, 'discounts']);
            Route::post('/try-discounts/{quote}', [QuoteController::class, 'tryDiscounts']);
            Route::get('/review/{quote}', [QuoteController::class, 'review']);
            Route::post('state', [QuoteController::class, 'storeState']);
            Route::patch('version/{quote}', [QuoteController::class, 'setVersion']);

            /*
             * Drafted Quotes
             */
            Route::apiResource('drafted', QuoteDraftedController::class, ['only' => ROUTE_RD]);
            Route::patch('drafted/{drafted}', [QuoteDraftedController::class, 'activate']);
            Route::put('drafted/{drafted}', [QuoteDraftedController::class, 'deactivate']);
            Route::delete('drafted/version/{version}', [QuoteDraftedController::class, 'destroyVersion']);

            /*
             * Submitted Quotes
             */
            Route::get('submitted/pdf/{submitted}', [QuoteSubmittedController::class, 'exportQuoteToPdf']);
            Route::get(
                'submitted/pdf/{submitted}/contract',
                [QuoteSubmittedController::class, 'exportContractOfQuoteToPdf']
            );
            Route::apiResource('submitted', QuoteSubmittedController::class, ['only' => ROUTE_RD]);
            Route::patch('submitted/{submitted}', [QuoteSubmittedController::class, 'activate']);
            Route::put('submitted/{submitted}', [QuoteSubmittedController::class, 'deactivate']);
            Route::put('submitted/copy/{submitted}', [QuoteSubmittedController::class, 'copy']);
            Route::put('submitted/unsubmit/{submitted}', [QuoteSubmittedController::class, 'unravelQuote']);
            Route::post('submitted/contract/{submitted}', [QuoteSubmittedController::class, 'createContract']);
            Route::put(
                'submitted/contract-template/{submitted}/{template}',
                [QuoteSubmittedController::class, 'setContractTemplate']
            );

            Route::get('file/{file}', [QuoteFileController::class, 'showQuoteFile']);
            Route::post('file', [QuoteFileController::class, 'storeQuoteFile']);

            /*
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

    /*
     * Rescue Quote attachments.
     */
    Route::get('quotes/{quote}/attachments', [RescueQuoteAttachmentController::class, 'showAttachmentsOfQuote']);
    Route::post('quotes/{quote}/attachments', [RescueQuoteAttachmentController::class, 'storeAttachmentForQuote']);
    Route::delete(
        'quotes/{quote}/attachments/{attachment:id}',
        [RescueQuoteAttachmentController::class, 'deleteAttachmentOfQuote']
    );

    Route::apiResource('ww-customers', WorldwideCustomerController::class, ['only' => ROUTE_R]);

    Route::get('pipeliner/pipeliner-sync-status', [PipelinerController::class, 'showPipelinerSyncStatus']);
    Route::get('pipeliner/pipeliner-sync-statistics', [PipelinerController::class, 'showPipelinerSyncStatistics']);
    Route::get('pipeliner/pipeliner-sync-queue-size', [PipelinerController::class, 'showPipelinerSyncQueueCounts']);
    Route::get('pipeliner/sync-errors', [PipelinerController::class, 'paginateSyncErrors']);
    Route::get('pipeliner/sync-errors/{error}', [PipelinerController::class, 'showSyncError']);
    Route::patch('pipeliner/sync-errors/{error}/archive', [PipelinerController::class, 'archiveSyncError']);
    Route::patch('pipeliner/sync-errors/batch-archive', [PipelinerController::class, 'batchArchiveSyncError']);
    Route::patch('pipeliner/sync-errors/all-archive', [PipelinerController::class, 'archiveAllSyncErrors']);
    Route::patch('pipeliner/sync-errors/{error}/restore', [PipelinerController::class, 'restoreSyncError']);
    Route::patch('pipeliner/sync-errors/batch-restore', [PipelinerController::class, 'batchRestoreSyncError']);
    Route::patch('pipeliner/sync-errors/all-restore', [PipelinerController::class, 'restoreAllSyncErrors']);
    Route::patch('pipeliner/queue-pipeliner-sync', [PipelinerController::class, 'queuePipelinerSync']);
    Route::patch('pipeliner/sync-model', [PipelinerController::class, 'syncModel']);

    Route::get('opportunities', [OpportunityController::class, 'paginateOpportunities']);
    Route::get('opportunities/filters', [OpportunityController::class, 'showOpportunityFilters']);
    Route::get('opportunities/lost', [OpportunityController::class, 'paginateLostOpportunities']);
    Route::post('opportunities/upload', [OpportunityController::class, 'batchUploadOpportunities']);
    Route::patch('opportunities/save', [OpportunityController::class, 'batchSaveOpportunities']);
    Route::get('opportunities/pipeliner-sync-status', [PipelinerController::class, 'showPipelinerSyncStatus']);
    Route::patch('opportunities/queue-pipeliner-sync', [PipelinerController::class, 'queuePipelinerSync']);
    Route::get('opportunities/{opportunity}', [OpportunityController::class, 'showOpportunity']);
    Route::post('opportunities', [OpportunityController::class, 'storeOpportunity']);
    Route::patch('opportunities/{opportunity}', [OpportunityController::class, 'updateOpportunity']);
    Route::delete('opportunities/{opportunity}', [OpportunityController::class, 'destroyOpportunity']);
    Route::patch(
        'opportunities/{opportunity}/ownership',
        [OpportunityOwnershipController::class, 'changeOpportunityOwnership']
    );
    Route::patch('opportunities/{opportunity}/stage', [OpportunityController::class, 'setStageOfOpportunity']);
    Route::patch('opportunities/{opportunity}/lost', [OpportunityController::class, 'markOpportunityAsLost']);
    Route::patch(
        'opportunities/{opportunity}/restore-from-lost',
        [OpportunityController::class, 'markOpportunityAsNotLost']
    );

    Route::get(
        'opportunities/{opportunity}/appointments',
        [OpportunityController::class, 'showAppointmentsOfOpportunity']
    );

    Route::get('opportunities/{opportunity}/notes', [OpportunityNoteController::class, 'paginateOpportunityNotes']);
    Route::post('opportunities/{opportunity}/notes', [OpportunityNoteController::class, 'storeOpportunityNote']);
    Route::get('opportunity-notes/{note}', [OpportunityNoteController::class, 'showOpportunityNote']);
    Route::patch('opportunity-notes/{note}', [OpportunityNoteController::class, 'updateOpportunityNote']);
    Route::delete('opportunity-notes/{note}', [OpportunityNoteController::class, 'deleteOpportunityNote']);

    Route::get('opportunities/{opportunity}/attachments', [OpportunityAttachmentController::class, 'listAttachments']);
    Route::post('opportunities/{opportunity}/attachments', [OpportunityAttachmentController::class, 'storeAttachment']);
    Route::delete(
        'opportunities/{opportunity}/attachments/{attachment:id}',
        [OpportunityAttachmentController::class, 'deleteAttachment']
    );

    Route::get('opportunity-forms', [OpportunityFormController::class, 'paginateOpportunityForms']);
    Route::get('opportunity-forms/{opportunity_form}', [OpportunityFormController::class, 'showOpportunityForm']);
    Route::post('opportunity-forms', [OpportunityFormController::class, 'storeOpportunityForm']);
    Route::post('opportunity-forms/{opportunity_form}/copy', [OpportunityFormController::class, 'copyOpportunityForm']);
    Route::patch('opportunity-forms/{opportunity_form}', [OpportunityFormController::class, 'updateOpportunityForm']);
    Route::patch(
        'opportunity-forms/{opportunity_form}/schema',
        [OpportunityFormController::class, 'updateSchemaOfOpportunityForm']
    );
    Route::delete('opportunity-forms/{opportunity_form}', [OpportunityFormController::class, 'deleteOpportunityForm']);

    /*
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
    Route::patch('sales-orders/{sales_order}/refresh-status', [SalesOrderController::class, 'refreshSalesOrderStatus']);

    /*
     *  Worldwide Quotes.
     */
    Route::get('ww-quotes/drafted', WorldwideQuoteDraftedController::class);
    Route::get('ww-quotes/drafted/dead', [WorldwideQuoteDraftedController::class, 'paginateDeadDraftedQuotes']);
    Route::get('ww-quotes/submitted', WorldwideQuoteSubmittedController::class);
    Route::get('ww-quotes/submitted/dead', [WorldwideQuoteSubmittedController::class, 'paginateDeadSubmittedQuotes']);

    Route::post('ww-quotes', [WorldwideQuoteController::class, 'initializeQuote']);
    Route::put('ww-quotes/{worldwide_quote}/copy', [WorldwideQuoteController::class, 'replicateQuote']);
    Route::post('ww-quotes/{worldwide_quote}/versions', [WorldwideQuoteController::class, 'createVersionOfQuote']);
    Route::post(
        'ww-quotes/{worldwide_quote}/versions/{version:id}',
        [WorldwideQuoteController::class, 'createVersionOfQuoteFromVersion']
    );
    Route::patch(
        'ww-quotes/{worldwide_quote}/versions/{version:id}',
        [WorldwideQuoteController::class, 'switchActiveVersionOfQuote']
    );
    Route::delete(
        'ww-quotes/{worldwide_quote}/versions/{version:id}',
        [WorldwideQuoteController::class, 'destroyQuoteVersion']
    );
    Route::get('ww-quotes/{worldwide_quote}', [WorldwideQuoteController::class, 'showQuoteState']);

    Route::get('ww-quotes/{worldwide_quote}/export', [WorldwideQuoteController::class, 'exportQuote']);
    Route::post('ww-quotes/{worldwide_quote}/submit', [WorldwideQuoteController::class, 'submitQuote']);
    Route::post('ww-quotes/{worldwide_quote}/draft', [WorldwideQuoteController::class, 'draftQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/unravel', [WorldwideQuoteController::class, 'unravelQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/activate', [WorldwideQuoteController::class, 'activateQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/deactivate', [WorldwideQuoteController::class, 'deactivateQuote']);
    Route::patch('ww-quotes/{worldwide_quote}/dead', [WorldwideQuoteController::class, 'markQuoteAsDead']);
    Route::patch(
        'ww-quotes/{worldwide_quote}/restore-from-dead',
        [WorldwideQuoteController::class, 'markQuoteAsAlive']
    );
    Route::patch('ww-quotes/{worldwide_quote}/ownership',
        [WorldwideQuoteOwnershipController::class, 'changeQuoteOwnership']);
    Route::get(
        'ww-quotes/{worldwide_quote}/files/distributor-files',
        [WorldwideQuoteController::class, 'downloadQuoteDistributorFiles']
    );
    Route::get(
        'ww-quotes/{worldwide_quote}/files/schedule-files',
        [WorldwideQuoteController::class, 'downloadQuoteScheduleFiles']
    );

    Route::get('ww-quotes/{worldwide_quote}/notes', [WorldwideQuoteNoteController::class, 'paginateQuoteNotes']);
    Route::get('ww-quotes/{worldwide_quote}/notes/{note:id}', [WorldwideQuoteNoteController::class, 'showQuoteNote']);
    Route::post('ww-quotes/{worldwide_quote}/notes', [WorldwideQuoteNoteController::class, 'storeQuoteNote']);
    Route::patch(
        'ww-quotes/{worldwide_quote}/notes/{note:id}',
        [WorldwideQuoteNoteController::class, 'updateQuoteNote']
    );
    Route::delete(
        'ww-quotes/{worldwide_quote}/notes/{note:id}',
        [WorldwideQuoteNoteController::class, 'destroyQuoteNote']
    );

    Route::get('ww-quotes/{worldwide_quote}/tasks', [QuoteTaskController::class, 'paginateWorldwideQuoteTasks']);
    Route::get('ww-quotes/{worldwide_quote}/tasks/{task:id}', [QuoteTaskController::class, 'showWorldwideQuoteTask']);
    Route::post('ww-quotes/{worldwide_quote}/tasks', [QuoteTaskController::class, 'storeWorldwideQuoteTask']);
    Route::patch(
        'ww-quotes/{worldwide_quote}/tasks/{task:id}',
        [QuoteTaskController::class, 'updateWorldwideQuoteTask']
    );
    Route::delete(
        'ww-quotes/{worldwide_quote}/tasks/{task:id}',
        [QuoteTaskController::class, 'destroyWorldwideQuoteTask']
    );

    Route::get(
        'ww-quotes/{worldwide_quote}/appointments',
        [WorldwideQuoteController::class, 'showAppointmentsOfQuote']
    );

    Route::get(
        'ww-quotes/{worldwide_quote}/sales-order-data',
        [WorldwideQuoteController::class, 'showSalesOrderDataOfWorldwideQuote']
    );

    /*
     * Worldwide Quote attachments.
     */
    Route::get(
        'ww-quotes/{worldwide_quote}/attachments',
        [WorldwideQuoteAttachmentController::class, 'showAttachmentsOfWorldwideQuote']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/attachments',
        [WorldwideQuoteAttachmentController::class, 'storeAttachmentForWorldwideQuote']
    );
    Route::delete(
        'ww-quotes/{worldwide_quote}/attachments/{attachment:id}',
        [WorldwideQuoteAttachmentController::class, 'deleteAttachmentOfQuote']
    );

    /*
     * Worldwide Pack Quote.
     */
    Route::post('ww-quotes/{worldwide_quote}/assets', [WorldwideQuoteAssetController::class, 'initializeQuoteAsset']);
    Route::put(
        'ww-quotes/{worldwide_quote}/assets',
        [WorldwideQuoteAssetController::class, 'batchInitializeQuoteAsset']
    );
    Route::patch(
        'ww-quotes/{worldwide_quote}/assets',
        [WorldwideQuoteAssetController::class, 'batchUpdateQuoteAssets']
    );
    Route::delete(
        'ww-quotes/{worldwide_quote}/assets/{asset:id}',
        [WorldwideQuoteAssetController::class, 'destroyQuoteAsset']
    );
    Route::delete(
        'ww-quotes/{worldwide_quote}/assets',
        [WorldwideQuoteAssetController::class, 'batchDestroyQuoteAsset']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/assets/lookup',
        [WorldwideQuoteAssetController::class, 'batchWarrantyLookup']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/assets/upload',
        [WorldwideQuoteAssetController::class, 'uploadBatchQuoteAssetsFile']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/assets/import',
        [WorldwideQuoteAssetController::class, 'importBatchQuoteAssetsFile']
    );

    Route::post(
        'ww-quotes/{worldwide_quote}/contacts',
        [WorldwideQuoteController::class, 'processQuoteAddressesContactsStep']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/assets-review',
        [WorldwideQuoteController::class, 'processQuoteAssetsReviewStep']
    );
    Route::post('ww-quotes/{worldwide_quote}/margin', [WorldwideQuoteController::class, 'processQuoteMarginStep']);
    Route::get(
        'ww-quotes/{worldwide_quote}/applicable-discounts',
        [WorldwideQuoteController::class, 'showPackQuoteApplicableDiscounts']
    );
    Route::post('ww-quotes/{worldwide_quote}/discounts', [WorldwideQuoteController::class, 'processQuoteDiscountStep']);
    Route::post('ww-quotes/{worldwide_quote}/details', [WorldwideQuoteController::class, 'processQuoteDetailsStep']);

    Route::get(
        'ww-quotes/{worldwide_quote}/assets-groups/{assets_group:id}',
        [WorldwideQuoteAssetController::class, 'showGroupOfAssets']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/assets-groups',
        [WorldwideQuoteAssetController::class, 'storeGroupOfAssets']
    );
    Route::patch(
        'ww-quotes/{worldwide_quote}/assets-groups/{assets_group:id}',
        [WorldwideQuoteAssetController::class, 'updateGroupOfAssets']
    );
    Route::delete(
        'ww-quotes/{worldwide_quote}/assets-groups/{assets_group:id}',
        [WorldwideQuoteAssetController::class, 'deleteGroupOfAssets']
    );
    Route::put(
        'ww-quotes/{worldwide_quote}/assets-groups',
        [WorldwideQuoteAssetController::class, 'moveAssetsBetweenGroupsOfAssets']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/assets-lookup',
        [WorldwideQuoteAssetController::class, 'performAssetsLookup']
    );

    Route::post(
        'ww-quotes/{worldwide_quote}/contract/country-margin-tax-price-summary',
        [WorldwideQuoteController::class, 'showPriceSummaryOfContractQuoteAfterCountryMarginTax']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/pack/country-margin-tax-price-summary',
        [WorldwideQuoteController::class, 'showPriceSummaryOfPackQuoteAfterCountryMarginTax']
    );

    Route::post(
        'ww-quotes/{worldwide_quote}/contract/discounts-price-summary',
        [WorldwideQuoteController::class, 'showPriceSummaryOfContractQuoteAfterDiscounts']
    );
    Route::post(
        'ww-quotes/{worldwide_quote}/pack/discounts-price-summary',
        [WorldwideQuoteController::class, 'showPriceSummaryOfPackQuoteAfterDiscounts']
    );

    Route::get('ww-quotes/{worldwide_quote}/validate', [WorldwideQuoteController::class, 'validateQuote']);

    /*
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

    Route::post(
        'ww-distributions/{worldwide_distribution}/distributor-file',
        [WorldwideDistributionController::class, 'storeDistributorFile']
    );
    Route::post(
        'ww-distributions/{worldwide_distribution}/schedule-file',
        [WorldwideDistributionController::class, 'storeScheduleFile']
    );

    Route::post(
        'ww-distributions/{worldwide_distribution}/rows-groups',
        [WorldwideDistributionController::class, 'createRowsGroup']
    );
    Route::patch(
        'ww-distributions/{worldwide_distribution}/rows-groups/{rows_group:id}',
        [WorldwideDistributionController::class, 'updateRowsGroup']
    );
    Route::delete(
        'ww-distributions/{worldwide_distribution}/rows-groups/{rows_group:id}',
        [WorldwideDistributionController::class, 'deleteRowsGroup']
    );
    Route::put(
        'ww-distributions/{worldwide_distribution}/rows-groups',
        [WorldwideDistributionController::class, 'moveRowsBetweenGroups']
    );
    Route::post(
        'ww-distributions/{worldwide_distribution}/rows-lookup',
        [WorldwideDistributionController::class, 'performRowsLookup']
    );
    Route::get(
        'ww-distributions/{worldwide_distribution}/applicable-discounts',
        [WorldwideDistributionController::class, 'showDistributionApplicableDiscounts']
    );
    Route::post(
        'ww-distributions/{worldwide_distribution}/discounts-margin',
        [WorldwideDistributionController::class, 'showMarginAfterPredefinedDiscounts']
    );
    Route::post(
        'ww-distributions/{worldwide_distribution}/custom-discount-margin',
        [WorldwideDistributionController::class, 'showMarginAfterCustomDiscount']
    );
    Route::post(
        'ww-distributions/{worldwide_distribution}/country-margin-tax-margin',
        [WorldwideDistributionController::class, 'showPriceSummaryAfterMarginTax']
    );

    Route::get(
        'ww-distributions/{worldwide_distribution}/mapped-rows/{mapped_row:id}',
        [WorldwideDistributionController::class, 'showMappedRow']
    );
    Route::patch(
        'ww-distributions/{worldwide_distribution}/mapped-rows/{mapped_row:id}',
        [WorldwideDistributionController::class, 'updateMappedRow']
    );

    /*
     * Unified Quotes (Rescue & Worldwide).
     */
    Route::get('unified-quotes/expiring', [UnifiedQuoteController::class, 'paginateUnifiedExpiringQuotes']);
    Route::get('unified-quotes/submitted', [UnifiedQuoteController::class, 'paginateUnifiedSubmittedQuotes']);
    Route::get('unified-quotes/drafted', [UnifiedQuoteController::class, 'paginateUnifiedDraftedQuotes']);

    /*
     * Spaces.
     */
    Route::get('spaces', SpaceController::class);
    Route::put('spaces', [SpaceController::class, 'batchPutSpaces']);

    /*
     * Pipelines.
     */
    Route::get('pipelines', [PipelineController::class, 'paginatePipelines']);
    Route::get('pipelines/list', [PipelineController::class, 'showListOfPipelines']);
    Route::get(
        'pipelines/list/without-opportunity-form',
        [PipelineController::class, 'showListOfPipelinesWithoutOpportunityForm']
    );
    Route::get('pipelines/default', [PipelineController::class, 'showDefaultPipeline']);
    Route::get(
        'pipelines/default/stage-opportunity',
        [OpportunityController::class, 'showOpportunitiesGroupedByPipelineStages']
    );
    Route::get('pipelines/{pipeline}', [PipelineController::class, 'showPipeline']);
    Route::get(
        'pipelines/default/opportunity-form',
        [PipelineController::class, 'showOpportunityFormSchemaOfDefaultPipeline']
    );
    Route::get(
        'pipelines/{pipeline}/opportunity-form',
        [PipelineController::class, 'showOpportunityFormSchemaOfPipeline']
    );
    Route::post('pipelines', [PipelineController::class, 'storePipeline']);
    Route::put('pipelines', [PipelineController::class, 'bulkUpdatePipelines']);
    Route::patch('pipelines/{pipeline}', [PipelineController::class, 'updatePipeline']);
    Route::patch('pipelines/{pipeline}/default', [PipelineController::class, 'markPipelineAsDefault']);
    Route::delete('pipelines/{pipeline}', [PipelineController::class, 'deletePipeline']);
    Route::get(
        'pipeline-stages/{stage}/opportunities',
        [OpportunityController::class, 'paginateOpportunitiesOfPipelineStage']
    );

    /*
     * Sales Units.
     */
    Route::get('sales-units/list', [SalesUnitController::class, 'showListOfSalesUnits']);
    Route::put('sales-units', [SalesUnitController::class, 'bulkCreateOrUpdateSalesUnits']);

    Route::get('tasks/taskable/{taskable}', [TaskController::class, 'listTasksOfTaskable'])->whereUuid('taskable');
    Route::post('tasks', [TaskController::class, 'createTask']);
    Route::get('tasks/{task}', [TaskController::class, 'showTask']);
    Route::patch('tasks/{task}', [TaskController::class, 'updateTask']);
    Route::delete('tasks/{task}', [TaskController::class, 'deleteTask']);
    Route::put('task-reminders/{reminder}', [TaskController::class, 'setTaskReminder']);
    Route::delete('task-reminders/{reminder}', [TaskController::class, 'deleteTaskReminder']);

    Route::post('appointments', [AppointmentController::class, 'storeAppointment']);
    Route::get('appointments/{appointment}', [AppointmentController::class, 'showAppointment']);
    Route::patch('appointments/{appointment}', [AppointmentController::class, 'updateAppointment']);
    Route::delete('appointments/{appointment}', [AppointmentController::class, 'deleteAppointment']);
    Route::put('appointment-reminders/{reminder}', [AppointmentController::class, 'setAppointmentReminder']);
    Route::delete('appointment-reminders/{reminder}', [AppointmentController::class, 'deleteAppointmentReminder']);
});

Route::post('pipeliner/webhook', [PipelinerWebhookController::class, 'handleWebhookEvent'])->name('pipeliner.webhook');

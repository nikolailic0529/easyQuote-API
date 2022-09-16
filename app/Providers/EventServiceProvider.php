<?php

namespace App\Providers;

use App\Events\{Company\CompanyUpdated,
    CustomField\CustomFieldValuesUpdated,
    ExchangeRatesUpdated,
    Opportunity\OpportunityUpdated,
    Permission\GrantedModulePermission,
    RfqReceived,
    SalesUnit\SalesUnitsUpdated,
    WorldwideQuote\WorldwideQuoteNoteCreated,
    WorldwideQuote\WorldwideQuoteSubmitted};
use App\Listeners\{AddressEventAuditor,
    AttachmentEventAuditor,
    CompanyEventAuditor,
    CompanyNoteAuditor,
    ContactEventAuditor,
    FlushPipelinerModelScrollCursorsOnUnitsUpdate,
    MailEventRateLimitingSubscriber,
    SyncCustomFieldValuesInPipeliner,
    DocumentMappingSyncSubscriber,
    ExchangeRatesListener,
    ImportableColumnEventAuditor,
    LogSentMessage,
    MigrateWorldwideQuoteAssets,
    ModulePermissionListener,
    NoteEventAuditor,
    NotifyNoteCreatedOnWorldwideQuote,
    OpportunityEventAuditor,
    OpportunityFormEventAuditor,
    PipelineEventAuditor,
    PipelinerSyncEventSubscriber,
    RescueQuoteEventAuditor,
    RfqReceivedListener,
    SalesOrderEventAuditor,
    StatsDependentEntityEventSubscriber,
    SyncOpportunityPrimaryAccountContacts,
    SyncWorldwideContractQuoteWithOpportunityData,
    TaskEventSubscriber,
    TeamEventSubscriber,
    WorldwideQuoteEventAuditor};
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSent;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [

        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        MessageSent::class => [
            LogSentMessage::class,
        ],

        RfqReceived::class => [
            RfqReceivedListener::class,
        ],

        ExchangeRatesUpdated::class => [
            ExchangeRatesListener::class,
        ],

        GrantedModulePermission::class => [
            ModulePermissionListener::class,
        ],

        OpportunityUpdated::class => [
            SyncWorldwideContractQuoteWithOpportunityData::class,
        ],

        CompanyUpdated::class => [
            SyncOpportunityPrimaryAccountContacts::class,
        ],

        WorldwideQuoteSubmitted::class => [
            MigrateWorldwideQuoteAssets::class,
        ],

        CustomFieldValuesUpdated::class => [
            SyncCustomFieldValuesInPipeliner::class,
        ],

        SalesUnitsUpdated::class => [
            FlushPipelinerModelScrollCursorsOnUnitsUpdate::class,
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [

        MailEventRateLimitingSubscriber::class,

        TaskEventSubscriber::class,

        OpportunityEventAuditor::class,

        SalesOrderEventAuditor::class,

        TeamEventSubscriber::class,

        WorldwideQuoteEventAuditor::class,

        RescueQuoteEventAuditor::class,

        StatsDependentEntityEventSubscriber::class,

        PipelineEventAuditor::class,

        OpportunityFormEventAuditor::class,

        ImportableColumnEventAuditor::class,

        DocumentMappingSyncSubscriber::class,

        AttachmentEventAuditor::class,

        CompanyNoteAuditor::class,

        AddressEventAuditor::class,

        ContactEventAuditor::class,

        CompanyEventAuditor::class,

        PipelinerSyncEventSubscriber::class,

        NoteEventAuditor::class,

    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}

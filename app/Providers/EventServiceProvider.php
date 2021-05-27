<?php

namespace App\Providers;

use App\Events\{Company\CompanyUpdated,
    ExchangeRatesUpdated,
    Opportunity\OpportunityUpdated,
    Permission\GrantedModulePermission,
    RfqReceived,
    WorldwideQuote\WorldwideQuoteNoteCreated};
use App\Listeners\{ExchangeRatesListener,
    LogSentMessage,
    ModulePermissionListener,
    NotifyNoteCreatedOnWorldwideQuote,
    OpportunityEventAuditor,
    OpportunityFormEventAuditor,
    PipelineEventAuditor,
    RescueQuoteEventAuditor,
    RfqReceivedListener,
    SalesOrderEventSubscriber,
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

        WorldwideQuoteNoteCreated::class => [
            NotifyNoteCreatedOnWorldwideQuote::class,
        ],

        OpportunityUpdated::class => [
            SyncWorldwideContractQuoteWithOpportunityData::class,
        ],

        CompanyUpdated::class => [
            SyncOpportunityPrimaryAccountContacts::class,
        ]
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [

        TaskEventSubscriber::class,

        OpportunityEventAuditor::class,

        SalesOrderEventSubscriber::class,

        TeamEventSubscriber::class,

        WorldwideQuoteEventAuditor::class,

        RescueQuoteEventAuditor::class,

        StatsDependentEntityEventSubscriber::class,

        PipelineEventAuditor::class,

        OpportunityFormEventAuditor::class,

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

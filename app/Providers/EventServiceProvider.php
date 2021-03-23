<?php

namespace App\Providers;

use App\Events\{ExchangeRatesUpdated,
    Opportunity\OpportunityUpdated,
    Permission\GrantedModulePermission,
    RfqReceived,
    WorldwideQuote\WorldwideQuoteNoteCreated};
use App\Listeners\{ExchangeRatesListener,
    LogSentMessage,
    ModulePermissionListener,
    NotifyNoteCreatedOnWorldwideQuote,
    OpportunityEventSubscriber,
    RfqReceivedListener,
    SalesOrderEventSubscriber,
    SyncOpportunitySuppliersWithWorldwideContractQuote,
    TaskEventSubscriber,
    TeamEventSubscriber};
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
            SyncOpportunitySuppliersWithWorldwideContractQuote::class
        ]
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [

        TaskEventSubscriber::class,

        OpportunityEventSubscriber::class,

        SalesOrderEventSubscriber::class,

        TeamEventSubscriber::class,

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

<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        \Illuminate\Auth\Events\Registered::class => [
            \Illuminate\Auth\Listeners\SendEmailVerificationNotification::class,
        ],
        \Illuminate\Mail\Events\MessageSent::class => [
            \App\Listeners\LogSentMessage::class
        ],
        \App\Events\RfqReceived::class => [
            \App\Listeners\RfqReceivedListener::class
        ],
        \App\Events\ExchangeRatesUpdated::class => [
            \App\Listeners\ExchangeRatesListener::class
        ],
        \App\Events\Permission\GrantedModulePermission::class => [
            \App\Listeners\ModulePermissionListener::class
        ],
    ];

    /**
     * The subscriber classes to register.
     *
     * @var array
     */
    protected $subscribe = [
        \App\Listeners\EloquentEventSubscriber::class,
        \App\Listeners\TaskEventSubscriber::class,
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

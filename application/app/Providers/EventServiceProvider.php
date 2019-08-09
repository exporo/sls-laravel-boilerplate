<?php

namespace App\Providers;

use App\Events\Count;
use App\Listeners\CounterCache;
use App\Listeners\CounterDb;
use App\Listeners\CounterS3;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Count::class => [
            CounterCache::class,
            CounterDb::class,
            CounterS3::class
        ]
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

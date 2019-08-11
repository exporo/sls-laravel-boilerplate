<?php

namespace App\Providers;

use App\Events\Count;
use App\Listeners\CounterCache;
use App\Listeners\CounterDb;
use App\Listeners\CounterS3;
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
            CounterS3::class,
            CounterCache::class,
            CounterDb::class
        ],
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

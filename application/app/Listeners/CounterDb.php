<?php

namespace App\Listeners;

use App\Count;
use App\CountDb;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CounterDb
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object $event
     * @return void
     */
    public function handle($event)
    {
        if (!$count = CountDb::first()) {
            return CountDb::create(['counter' => 1]);
        }

        \DB::table('count')->increment('counter', 1);
    }


}

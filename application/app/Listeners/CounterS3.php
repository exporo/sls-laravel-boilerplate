<?php

namespace App\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class CounterS3
{
    const FILENAME = 'counter.json';

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
        if (!\Storage::exists(self::FILENAME)) {
            return \Storage::put(self::FILENAME, 1);
        }

        $counter = \Storage::get(self::FILENAME);
        \Storage::put(self::FILENAME, $counter + 1);
    }
}
